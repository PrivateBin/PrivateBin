import * as jsc from '../lib/jsverify';
import { assert } from 'chai';

describe('nonshrink testing', () => {
    it('jsc.string should be shrinkable', () => {
        assert(jsc.string.shrink('test').length !== 0);
    });
    it('jsc.nonshrink(jsc.string) should not be shrinkable', () => {
        assert(jsc.nonshrink(jsc.string).shrink('test').length === 0);
    });
});