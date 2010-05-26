#!/usr/bin/env perl

# Convert numbers to hex, when doing so is likely to increase compressibility.
# This actually makes the script slightly longer, but generally makes it compress
# to something shorter.
#
# Here we're targeting constants like 0xFF, 0xFFFF0000, 0x10101, 0x100000000, etc.

sub digitize {
    my $number = shift;
    if ($number >= 256) {
	my $nn = `printf "%x" $number`;
	if ($nn =~ /^[01f]+$/i) { return "0x$nn"; }
    }
    return $number;
}

while (<>) {
    s/([^a-zA-Z0-9_])(\d+)/$1 . digitize $2/eg;
    print;
}

