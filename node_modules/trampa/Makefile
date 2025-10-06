all : test

.PHONY : all test jshint david mocha istanbul dist

SRC=index.js

BINDIR=node_modules/.bin

MOCHA=$(BINDIR)/_mocha
ISTANBUL=$(BINDIR)/istanbul
JSHINT=$(BINDIR)/jshint
ESLINT=$(BINDIR)/eslint
DAVID=$(BINDIR)/david
LJS=$(BINDIR)/ljs

test : jshint eslint mocha istanbul david

jshint :
	$(JSHINT) $(SRC)

eslint :
	$(ESLINT) $(SRC)

david :
	$(DAVID)

mocha :
	$(MOCHA) --reporter=spec test

istanbul :
	$(ISTANBUL) cover -- $(MOCHA) --timeout 5000 test
	$(ISTANBUL) check-coverage --statements 100 --branches 100 --functions 100 --lines 100

README.md : index.js
	$(LJS) --no-code -o README.md index.js

dist : test README.md
	git clean -fdx -e node_modules
