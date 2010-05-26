#!/usr/bin/env perl

while (<>) {
  s/window\.sjcl\s*=/var sjcl=/g;
  s/window\.sjcl/sjcl/g;
  print;
}

