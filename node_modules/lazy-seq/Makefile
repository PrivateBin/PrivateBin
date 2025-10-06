all : test

.PHONY : all test jshint eslint jscs mocha istanbul ljs david README.md dist

BINDIR=node_modules/.bin

MOCHA=$(BINDIR)/mocha
IMOCHA=$(BINDIR)/_mocha
ISTANBUL=$(BINDIR)/istanbul
JSHINT=$(BINDIR)/jshint
ESLINT=$(BINDIR)/eslint
JSCS=$(BINDIR)/jscs
DAVID=$(BINDIR)/david
LJS=$(BINDIR)/ljs

SRC=index.js test

test : jshint eslint jscs mocha istanbul david

jshint :
	$(JSHINT) $(SRC)

eslint :
	$(ESLINT) $(SRC)

jscs :
	$(JSCS) $(SRC)

mocha :
	$(MOCHA) --reporter=spec test

istanbul :
	$(ISTANBUL) cover $(IMOCHA) test
	$(ISTANBUL) check-coverage --statements 100 --branches 100 --functions 100 --lines 100

ljs : README.md

README.md :
	$(LJS) --no-code -o README.md index.js

david :
	$(DAVID)

dist : test ljs
	git clean -fdx -e node_modules
