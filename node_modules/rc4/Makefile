all : test

.PHONY : all test jshint david mocha istanbul dist

BINDIR=node_modules/.bin

MOCHA=$(BINDIR)/_mocha
ISTANBUL=$(BINDIR)/istanbul
JSHINT=$(BINDIR)/jshint
ESLINT=$(BINDIR)/eslint
JSCS=$(BINDIR)/jscs
DAVID=$(BINDIR)/david
LJS=$(BINDIR)/ljs

test : jshint mocha istanbul david

jshint :
	$(JSHINT) rc4.js

eslint :
	$(ESLINT) rc4.js

jscs :
	$(JSCS) rc4.js

david :
	$(DAVID)

mocha :
	$(MOCHA) --reporter=spec test

istanbul :
	$(ISTANBUL) cover -- $(MOCHA) --timeout 5000 test
	$(ISTANBUL) check-coverage --statements 100 --branches 100 --functions 100 --lines 100

dist : test
	git clean -fdx -e node_modules
