#!/usr/bin/env perl

# This script is a hack.
#
# Opacify all non-private names by turning them into strings.
# That way, the Google compressor won't rename them.
#
# The script ignores properties whose names begin with _, because they
# are believed to be private.
#
# XXX TODO FIXME: this messes with strings, so it screws up exceptions.

my $script = join '', <>;

# remove comments
$script =~ s=/\*([^\*]|\*+[^\/])*\*/==g;
$script =~ s=//.*==g;

# stringify property names
$script =~ s=\.([a-zA-Z0-9][_a-zA-Z0-9]*)=['$1']=g;

# stringify sjcl
$script =~ s=(?:var\s+)?sjcl(\.|\s*\=)=window['sjcl']$1=g;

# stringify object notation
$script =~ s=([\{,]\s*)([a-zA-Z0-9][_a-zA-Z0-9]*):=$1'$2':=g;

# Export sjcl.  This is a bit of a hack, and might get replaced later.
print $script;

# not necessary with windowization.
# print "window\['sjcl'\] = sjcl;\n";
