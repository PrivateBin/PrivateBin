#!/usr/bin/env perl

# SJCL coding guidelines:
#
#   No tabs in any Javascript
#
#   Indentation is two spaces.  Alignment of stuff in multi-line statements is
#   encouraged.
#
#   Braces everywhere, following jslint.  I'm pretty sure the Closure compressor
#   removes them.  Semicolons at the end of every statement.
#
#   ++ and -- are allowed.
#
#   Variables are in camelCase.  I prefer underscore_separated, but JavaScript
#   uses camelCase everywhere.
#
#   Private members and methods are prefixed by underscores.
#
#   Constants (and only constants) are UNDERSCORE_SEPARATED_UPPER_CASE.  The
#   compression scripts rely on this.
#
#   Classes begin with a capital letter (not yet implemented.  Namespaces too?)
#
#   Block comments are not on the same line as code.

my $in_comment = 0;
my $file = '';

# for some reason, $. doesn't work.
my $line = 0;

while (<>) {
  if ($ARGV ne $file) {
    # opening a new file
    if ($in_comment) {
      print STDERR "Opening file $ARGV: comment from $file wasn't closed.\n";
    }
    $file = $ARGV;
    $in_comment = $line = 0;
  }
  $line ++;
  
  if (/\/\*(?:[^\*]|\*[^\/])*(\*\/\s*)?/) {
    # block comment on this line
    $in_comment = 1 unless defined $1;
    $ba = "$`$'";
    
    # shouldn't have code before or after it
    print STDERR "$file line $line: block comment and code together.\n" if $ba =~ /\S/;
    
    next;
  } elsif ($in_comment and /\*\//) {

    # leaving block comment
    $in_comment = 0;
    print STDERR "$file line $line: block comment and code together.\n" if $' =~ /\S/;
  }
  
  # don't enforce code style in a comment.
  next if $in_comment;

  reset;
  while (?[\.\s+\*/<>=,;:-]([a-zA-Z0-9_]+_[a-zA-Z0-9_]*)?) {
    # find variable names with underscores
    my $varname = $1;
    print STDERR "$file line $line: Variable name $varname contains an underscore\n"
      if $varname =~ /[a-z]/;
  }
  
  reset;
  print STDERR "$file line $line contains a tab\n" if /\t/;
}
