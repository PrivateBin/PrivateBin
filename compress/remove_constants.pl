#!/usr/bin/env perl

# This script is a hack.  It identifies things which it believes to be
# constant, then replaces them throughout the code.
#
# Constants are identified as properties declared in object notation
# with values consisting only of capital letters and underscores.  If
# the first character is an underscore, the constant is private, and
# can be removed entirely.
#
# The script dies if any two constants have the same property name but
# different values.
my $script = join '', <>;

# remove comments
$script =~ s=/\*([^\*]|\*+[^\/])*\*/==g;
$script =~ s=//.*==g;

sub preserve {
  my $stuff = shift;
  $stuff =~ s/,//;
  return $stuff;
}

my %constants = ();

sub add_constant {
  my ($name, $value) = @_;
  if (defined $constants{$name} && $constants{$name} ne $value) {
    print STDERR "variant constant $name = $value";
    die;
  } else {
    $constants{$name} = $value;
    #print STDERR "constant: $name = $value\n";
  }
}

# find private constants
while ($script =~
  s/([,\{]) \s*              # indicator that this is part of an object
    (_[A-Z0-9_]+) \s* : \s*  # all-caps variable name beginning with _
    (\d+|0x[0-9A-Fa-f]+) \s* # numeric value
    ([,\}])                  # next part of object
   /preserve "$1$4"/ex) {
   add_constant $2, $3;
}

my $script2 = '';

# find public constants
while ($script =~
  s/^(.*?)                   # beginning of script
    ([,\{]) \s*              # indicator that this is part of an object
    ([A-Z0-9_]+) \s* : \s*   # all-caps variable name
    (\d+|0x[0-9A-Fa-f]+) \s* # numeric value
    ([,\}])                  # next part of object([,\{]) \s*
   /$5/esx) {
   $script2 .= "$1$2$3:$4";
   add_constant $3, $4;
}

$script = "$script2$script";

foreach (keys %constants) {
  my $value = $constants{$_};
  $script =~ s/(?:[a-zA-Z0-9_]+\.)+$_(?=[^a-zA-Z0-9_])/$value/g;
}

print $script;
