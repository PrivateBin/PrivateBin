/**
 * Constructs a new bignum from another bignum, a number or a hex string.
 */
function bn(it) {
  this.initWith(it);
}

bn.prototype = {
  radix: 24,
  maxMul: 8,
  _class: bn,
  
  copy: function() {
    return new this._class(this);
  },

  /**
   * Initializes this with it, either as a bn, a number, or a hex string.
   */
  initWith: function(it) {
    var i=0, k, n, l;
    switch(typeof it) {
    case "object":
      this.limbs = it.limbs.slice(0);
      break;
      
    case "number":
      this.limbs = [it];
      this.normalize();
      break;
      
    case "string":
      it = it.replace(/^0x/, '');
      this.limbs = [];
      // hack
      k = this.radix / 4;
      for (i=0; i < it.length; i+=k) {
        this.limbs.push(parseInt(it.substring(Math.max(it.length - i - k, 0), it.length - i),16));
      }
      break;

    default:
      this.limbs = [0];
    }
    return this;
  },

  /**
   * Returns true if "this" and "that" are equal.  Calls fullReduce().
   * Equality test is in constant time.
   */
  equals: function(that) {
    if (typeof that == "number") { that = new this._class(that); }
    var difference = 0, i;
    this.fullReduce();
    that.fullReduce();
    for (i = 0; i < this.limbs.length || i < that.limbs.length; i++) {
      difference |= this.getLimb(i) ^ that.getLimb(i);
    }
    return (difference === 0);
  },
  
  /**
   * Get the i'th limb of this, zero if i is too large.
   */
  getLimb: function(i) {
    return (i >= this.limbs.length) ? 0 : this.limbs[i];
  },
  
  /**
   * Constant time comparison function.
   * Returns 1 if this >= that, or zero otherwise.
   */
  greaterEquals: function(that) {
    if (typeof that == "number") { that = new this._class(that); }
    var less = 0, greater = 0, i, a, b;
    i = Math.max(this.limbs.length, that.limbs.length) - 1;
    for (; i>= 0; i--) {
      a = this.getLimb(i);
      b = that.getLimb(i);
      greater |= (b - a) & ~less;
      less |= (a - b) & ~greater
    }
    return (greater | ~less) >>> 31;
  },
  
  /**
   * Convert to a hex string.
   */
  toString: function() {
    this.fullReduce();
    var out="", i, s, l = this.limbs;
    for (i=0; i < this.limbs.length; i++) {
      s = l[i].toString(16);
      while (i < this.limbs.length - 1 && s.length < 6) {
        s = "0" + s;
      }
      out = s + out;
    }
    return "0x"+out;
  },
  
  /** this += that.  Does not normalize. */
  addM: function(that) {
    if (typeof(that) !== "object") { that = new this._class(that); }
    var i;
    for (i=this.limbs.length; i<that.limbs.length; i++) {
      this.limbs[i] = 0;
    }
    for (i=0; i<that.limbs.length; i++) {
      this.limbs[i] += that.limbs[i];
    }
    return this;
  },

  /** this -= that.  Does not normalize. */
  subM: function(that) {
    if (typeof(that) !== "object") { that = new this._class(that); }
    var i;
    for (i=this.limbs.length; i<that.limbs.length; i++) {
      this.limbs[i] = 0;
    }
    for (i=0; i<that.limbs.length; i++) {
      this.limbs[i] -= that.limbs[i];
    }
    return this;
  },
  
  /** this + that.  Does not normalize. */
  add: function(that) {
    return this.copy().addM(that);
  },

  /** this - that.  Does not normalize. */
  sub: function(that) {
    return this.copy().subM(that);
  },
  
  /** this * that.  Normalizes and reduces. */
  mul: function(that) {
    if (typeof(that) == "number") { that = new this._class(that); }
    var i, j, a = this.limbs, b = that.limbs, al = a.length, bl = b.length, out = new this._class(), c = out.limbs, ai, ii=this.maxMul;

    for (i=0; i < this.limbs.length + that.limbs.length + 1; i++) {
      c[i] = 0;
    }
    for (i=0; i<al; i++) {
      ai = a[i];
      for (j=0; j<bl; j++) {
        c[i+j] += ai * b[j];
      }
     
      if (!--ii) {
        ii = this.maxMul;
        out.cnormalize();
      }
    }
    return out.cnormalize().reduce();
  },

  /** this ^ 2.  Normalizes and reduces. */
  square: function() {
    return this.mul(this);
  },

  /** this ^ n.  Uses square-and-multiply.  Normalizes and reduces. */
  power: function(l) {
    if (typeof(l) == "number") {
      l = [l];
    } else if (l.limbs !== undefined) {
      l = l.normalize().limbs;
    }
    var j, out = new this._class(1), pow = this;

    for (i=0; i<l.length; i++) {
      for (j=0; j<this.radix; j++) {
        if (l[i] & (1<<j)) {
          out = out.mul(pow);
        }
        pow = pow.square();
      }
    }
    
    return out;
  },

  /** Reduce mod a modulus.  Stubbed for subclassing. */
  reduce: function() {
    return this;
  },

  /** Reduce and normalize. */
  fullReduce: function() {
    return this.normalize();
  },
  
  /** Propagate carries. */
  normalize: function() {
    var carry=0, i, pv = this.placeVal, ipv = this.ipv, l, m, limbs = this.limbs, ll = limbs.length, mask = this.radixMask;
    for (i=0; i < ll || (carry !== 0 && carry !== -1); i++) {
      l = (limbs[i]||0) + carry;
      m = limbs[i] = l & mask;
      carry = (l-m)*ipv;
    }
    if (carry == -1) {
      limbs[i-1] -= this.placeVal;
    }
    return this;
  },

  /** Constant-time normalize. Does not allocate additional space. */
  cnormalize: function() {
    var carry=0, i, ipv = this.ipv, l, m, limbs = this.limbs, ll = limbs.length, mask = this.radixMask;
    for (i=0; i < ll-1; i++) {
      l = limbs[i] + carry;
      m = limbs[i] = l & mask;
      carry = (l-m)*ipv;
    }
    limbs[i] += carry;
    return this;
  }
};

/* Initialization routines for bignum library. */
(function init(){
  bn.prototype.ipv = 1 / (bn.prototype.placeVal = Math.pow(2,bn.prototype.radix));
  bn.prototype.radixMask = (1 << bn.prototype.radix) - 1;
})();

/**
 * Creates a new subclass of bn, based on reduction modulo a pseudo-Mersenne prime,
 * i.e. a prime of the form 2^e + sum(a * 2^b),where the sum is negative and sparse.
 */
function pseudoMersennePrime(exponent, coeff) {
  function p(it) {
    this.initWith(it);
    /*if (this.limbs[this.modOffset]) {
      this.reduce();
    }*/
  }

  var ppr = p.prototype = new bn(), i, tmp, mo;
  mo = ppr.modOffset = Math.ceil(tmp = exponent / ppr.radix);
  ppr.exponent = exponent;
  ppr.offset = [];
  ppr.factor = [];
  ppr.minOffset = mo;
  ppr.fullMask = 0;
  ppr.fullOffset = [];
  ppr.fullFactor = [];
  ppr.modulus = p.modulus = new bn(Math.pow(2,exponent));
  
  ppr.fullMask = 0|-Math.pow(2, exponent % ppr.radix);

  for (i=0; i<coeff.length; i++) {
    ppr.offset[i] = Math.floor(coeff[i][0] / ppr.radix - tmp);
    ppr.fullOffset[i] = Math.ceil(coeff[i][0] / ppr.radix - tmp);
    ppr.factor[i] = coeff[i][1] * Math.pow(1/2, exponent - coeff[i][0] + ppr.offset[i] * ppr.radix);
    ppr.fullFactor[i] = coeff[i][1] * Math.pow(1/2, exponent - coeff[i][0] + ppr.fullOffset[i] * ppr.radix);
    ppr.modulus.addM(new bn(Math.pow(2,coeff[i][0])*coeff[i][1]));
    ppr.minOffset = Math.min(ppr.minOffset, -ppr.offset[i]); // conservative
  }
  ppr._class = p;
  ppr.modulus.cnormalize();

  /** Approximate reduction mod p.  May leave a number which is negative or slightly larger than p. */
  ppr.reduce = function() {
    var i, k, l, mo = this.modOffset, limbs = this.limbs, aff, off = this.offset, ol = this.offset.length, fac = this.factor, ll;

    i = this.minOffset;
    while (limbs.length > mo) {
      l = limbs.pop();
      ll = limbs.length;
      for (k=0; k<ol; k++) {
        limbs[ll+off[k]] -= fac[k] * l;
      }
      
      i--;
      if (i == 0) {
        limbs.push(0);
        this.cnormalize();
        i = this.minOffset;
      }
    }
    this.cnormalize();

    return this;
  };
  
  ppr._strongReduce = (ppr.fullMask == -1) ? ppr.reduce : function() {
    var limbs = this.limbs, i = limbs.length - 1, l;
    this.reduce();
    if (i == this.modOffset - 1) {
      l = limbs[i] & this.fullMask;
      limbs[i] -= l;
      for (k=0; k<this.fullOffset.length; k++) {
        limbs[i+this.fullOffset[k]] -= this.fullFactor[k] * l;
      }
      this.normalize();
    }
  };

  /** mostly constant-time, very expensive full reduction. */
  ppr.fullReduce = function() {
    var greater, i;
    // massively above the modulus, may be negative
    
    this._strongReduce();
    // less than twice the modulus, may be negative

    this.addM(this.modulus);
    this.addM(this.modulus);
    this.normalize();
    // probably 2-3x the modulus
    
    this._strongReduce();
    // less than the power of 2.  still may be more than
    // the modulus

    // HACK: pad out to this length
    for (i=this.limbs.length; i<this.modOffset; i++) {
      this.limbs[i] = 0;
    }
    
    // constant-time subtract modulus
    greater = this.greaterEquals(this.modulus);
    for (i=0; i<this.limbs.length; i++) {
      this.limbs[i] -= this.modulus.limbs[i] * greater;
    }
    this.cnormalize();

    return this;
  };

  ppr.inverse = function() {
    return (this.power(this.modulus.sub(2)));
  };

  ppr.toBits = function() {
    this.fullReduce();
    var i=this.modOffset - 1, w=sjcl.bitArray, e = (this.exponent + 7 & -8) % this.radix || this.radix;
    out = [w.partial(e, this.getLimb(i))];
    for (i--; i >= 0; i--) {
      out = w.concat(out, [w.partial(this.radix, this.getLimb(i))]);
    }
    return out;
  };

  p.fromBits = function(bits) {
    
    var out = new this(), words=[], w=sjcl.bitArray, t = this.prototype,
        l = Math.min(w.bitLength(bits), t.exponent + 7 & -8), e = l % t.radix || t.radix;
    
    words[0] = w.extract(bits, 0, e);
    for (; e < l; e += t.radix) {
      words.unshift(w.extract(bits, e, t.radix));
    }

    out.limbs = words;
    return out;
  };

  return p;
}

// a small Mersenne prime
p127 = pseudoMersennePrime(127, [[0,-1]]);

// Bernstein's prime for Curve25519
p25519 = pseudoMersennePrime(255, [[0,-19]]);

// NIST primes
p192 = pseudoMersennePrime(192, [[0,-1],[64,-1]]);
p224 = pseudoMersennePrime(224, [[0,1],[96,-1]]);
p256 = pseudoMersennePrime(256, [[0,-1],[96,1],[192,1],[224,-1]]);
p384 = pseudoMersennePrime(384, [[0,-1],[32,1],[96,-1],[128,-1]]);
p521 = pseudoMersennePrime(521, [[0,-1]]);

bn.random = function(modulus, paranoia) {
  if (typeof modulus != "object") { modulus = new bn(modulus); }
  var words, i, l = modulus.limbs.length, m = modulus.limbs[l-1]+1, out = new bn();
  while (true) {
    // get a sequence whose first digits make sense
    do {
      words = sjcl.random.randomWords(l, paranoia);
      if (words[l-1] < 0) { words[l-1] += 0x100000000; }
    } while (Math.floor(words[l-1] / m) == Math.floor(0x100000000 / m));
    words[l-1] %= m;

    // mask off all the limbs
    for (i=0; i<l-1; i++) {
      words[i] &= modulus.radixMask;
    }

    // check the rest of the digitssj
    out.limbs = words;
    if (!out.greaterEquals(modulus)) {
      return out;
    }
  }
};

