'use strict';

(function () {
    let ret;

    async function initialize() {
        if (ret) return ret;

        const COMPRESSION_LEVEL = 7;
        const NO_ZLIB_HEADER = -1;
        const CHUNK_SIZE = 32 * 1024;
        const createModule = (await import('./zlib-1.3.2.js')).default;
        const Module = await createModule({map: {}});
        const srcPtr = Module.__malloc(CHUNK_SIZE);
        const dstPtr = Module.__malloc(CHUNK_SIZE);

        class RawDef {
            constructor() {
                this.zstreamPtr = Module.__createDeflateContext(COMPRESSION_LEVEL, NO_ZLIB_HEADER);
                Module.map[this.zstreamPtr] = this;
                this.offset = 0;
                this.buff = new Uint8Array(CHUNK_SIZE);
            }

            deflate(chunk, flush) {
                Module.HEAPU8.set(chunk, srcPtr);
                Module.__deflate(this.zstreamPtr, srcPtr, dstPtr, chunk.length, CHUNK_SIZE, flush);
            }

            onData(chunk) {
                if (this.buff.length < this.offset + chunk.length) {
                    const buff = this.buff;
                    this.buff = new Uint8Array(this.buff.length * 2);
                    this.buff.set(buff);
                }
                this.buff.set(chunk, this.offset);
                this.offset += chunk.length;
            }

            destroy() {
                Module.__freeDeflateContext(this.zstreamPtr);
                delete Module.map[this.zstreamPtr];
                this.buff = null;
            }

            getBuffer() {
                return this.buff.slice(0, this.offset);
            }
        }

        class RawInf {
            constructor() {
                this.zstreamPtr = Module.__createInflateContext(NO_ZLIB_HEADER);
                Module.map[this.zstreamPtr] = this;
                this.offset = 0;
                this.buff = new Uint8Array(CHUNK_SIZE);
            }

            inflate(chunk) {
                Module.HEAPU8.set(chunk, srcPtr);
                Module.__inflate(this.zstreamPtr, srcPtr, dstPtr, chunk.length, CHUNK_SIZE);
            }

            onData(chunk) {
                if (this.buff.length < this.offset + chunk.length) {
                    const buff = this.buff;
                    this.buff = new Uint8Array(this.buff.length * 2);
                    this.buff.set(buff);
                }
                this.buff.set(chunk, this.offset);
                this.offset += chunk.length;
            }

            destroy() {
                Module.__freeInflateContext(this.zstreamPtr);
                delete Module.map[this.zstreamPtr];
                this.buff = null;
            }

            getBuffer() {
                return this.buff.slice(0, this.offset);
            }
        }

        ret = {
            inflate(rawDeflateBuffer) {
                const rawInf = new RawInf();
                for (let offset = 0; offset < rawDeflateBuffer.length; offset += CHUNK_SIZE) {
                    const end = Math.min(offset + CHUNK_SIZE, rawDeflateBuffer.length);
                    const chunk = rawDeflateBuffer.subarray(offset, end);
                    rawInf.inflate(chunk);
                }
                const ret = rawInf.getBuffer();
                rawInf.destroy();
                return ret;
            },
            deflate(rawInflateBuffer) {
                const rawDef = new RawDef();
                for (let offset = 0; offset < rawInflateBuffer.length; offset += CHUNK_SIZE) {
                    const end = Math.min(offset + CHUNK_SIZE, rawInflateBuffer.length);
                    const chunk = rawInflateBuffer.subarray(offset, end);
                    rawDef.deflate(chunk, rawInflateBuffer.length <= offset + CHUNK_SIZE);
                }
                const ret = rawDef.getBuffer();
                rawDef.destroy();
                return ret;
            },
        };

        return ret;
    }
    this.zlib = initialize();
}).call(this);
