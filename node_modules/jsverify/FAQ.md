## FAQ

### Why do all the examples import the library as jsc instead of jsv?

Does JSC originate with [JSCheck](http://www.jscheck.org/)?

**A:** Yes

### smap requires an inverse function, which isn't always practical. Is this complexity related to shrinking?

**A:** Yes. We don't want to give an easy-to-use interface which forgets
shrinking altogether. Note, that *right* inverse is enough, which is most
likely easy to write, even *complete* inverse doesn't exist.
