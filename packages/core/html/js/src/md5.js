/*! JavaScript implementation of the Message Digest Algorithm, as defined in RFC 1321.
* Version 2.1 Copyright (C) Paul Johnston 1999 - 2002.
* Other contributors: Greg Holt, Andrew Kepert, Ydnar, Lostinet
* Distributed under the BSD License */
var hexcase=0,b64pad="",chrsz=8;
function hex_md5(c){return binl2hex(core_md5(str2binl(c),c.length*chrsz))}
function core_md5(c,g){c[g>>5]|=128<<g%32;c[(g+64>>>9<<4)+14]=g;for(var a=1732584193,b=-271733879,d=-1732584194,e=271733878,f=0;f<c.length;f+=16)var h=a,i=b,j=d,k=e,a=md5_ff(a,b,d,e,c[f+0],7,-680876936),e=md5_ff(e,a,b,d,c[f+1],12,-389564586),d=md5_ff(d,e,a,b,c[f+2],17,606105819),b=md5_ff(b,d,e,a,c[f+3],22,-1044525330),a=md5_ff(a,b,d,e,c[f+4],7,-176418897),e=md5_ff(e,a,b,d,c[f+5],12,1200080426),d=md5_ff(d,e,a,b,c[f+6],17,-1473231341),b=md5_ff(b,d,e,a,c[f+7],22,-45705983),a=md5_ff(a,b,d,e,c[f+8],7,1770035416),e=md5_ff(e,a,b,d,c[f+9],12,-1958414417),d=md5_ff(d,e,a,b,c[f+10],17,-42063),b=md5_ff(b,d,e,a,c[f+11],22,-1990404162),a=md5_ff(a,b,d,e,c[f+12],7,1804603682),e=md5_ff(e,a,b,d,c[f+13],12,-40341101),d=md5_ff(d,e,a,b,c[f+14],17,-1502002290),b=md5_ff(b,d,e,a,c[f+15],22,1236535329),a=md5_gg(a,b,d,e,c[f+1],5,-165796510),e=md5_gg(e,a,b,d,c[f+6],9,-1069501632),d=md5_gg(d,e,a,b,c[f+11],14,643717713),b=md5_gg(b,d,e,a,c[f+0],20,-373897302),a=md5_gg(a,b,d,e,c[f+5],5,-701558691),e=md5_gg(e,a,b,d,c[f+10],9,38016083),d=md5_gg(d,e,a,b,c[f+15],14,-660478335),b=md5_gg(b,d,e,a,c[f+4],20,-405537848),a=md5_gg(a,b,d,e,c[f+9],5,568446438),e=md5_gg(e,a,b,d,c[f+14],9,-1019803690),d=md5_gg(d,e,a,b,c[f+3],14,-187363961),b=md5_gg(b,d,e,a,c[f+8],20,1163531501),a=md5_gg(a,b,d,e,c[f+13],5,-1444681467),e=md5_gg(e,a,b,d,c[f+2],9,-51403784),d=md5_gg(d,e,a,b,c[f+7],14,1735328473),b=md5_gg(b,d,e,a,c[f+12],20,-1926607734),a=md5_hh(a,b,d,e,c[f+5],4,-378558),e=md5_hh(e,a,b,d,c[f+8],11,-2022574463),d=md5_hh(d,e,a,b,c[f+11],16,1839030562),b=md5_hh(b,d,e,a,c[f+14],23,-35309556),a=md5_hh(a,b,d,e,c[f+1],4,-1530992060),e=md5_hh(e,a,b,d,c[f+4],11,1272893353),d=md5_hh(d,e,a,b,c[f+7],16,-155497632),b=md5_hh(b,d,e,a,c[f+10],23,-1094730640),a=md5_hh(a,b,d,e,c[f+13],4,681279174),e=md5_hh(e,a,b,d,c[f+0],11,-358537222),d=md5_hh(d,e,a,b,c[f+3],16,-722521979),b=md5_hh(b,d,e,a,c[f+6],23,76029189),a=md5_hh(a,b,d,e,c[f+9],4,-640364487),e=md5_hh(e,a,b,d,c[f+12],11,-421815835),d=md5_hh(d,e,a,b,c[f+15],16,530742520),b=md5_hh(b,d,e,a,c[f+2],23,-995338651),a=md5_ii(a,b,d,e,c[f+0],6,-198630844),e=md5_ii(e,a,b,d,c[f+7],10,1126891415),d=md5_ii(d,e,a,b,c[f+14],15,-1416354905),b=md5_ii(b,d,e,a,c[f+5],21,-57434055),a=md5_ii(a,b,d,e,c[f+12],6,1700485571),e=md5_ii(e,a,b,d,c[f+3],10,-1894986606),d=md5_ii(d,e,a,b,c[f+10],15,-1051523),b=md5_ii(b,d,e,a,c[f+1],21,-2054922799),a=md5_ii(a,b,d,e,c[f+8],6,1873313359),e=md5_ii(e,a,b,d,c[f+15],10,-30611744),d=md5_ii(d,e,a,b,c[f+6],15,-1560198380),b=md5_ii(b,d,e,a,c[f+13],21,1309151649),a=md5_ii(a,b,d,e,c[f+4],6,-145523070),e=md5_ii(e,a,b,d,c[f+11],10,-1120210379),d=md5_ii(d,e,a,b,c[f+2],15,718787259),b=md5_ii(b,d,e,a,c[f+9],21,-343485551),a=safe_add(a,h),b=safe_add(b,i),d=safe_add(d,j),e=safe_add(e,k);return[a,b,d,e]}function md5_cmn(c,g,a,b,d,e){return safe_add(bit_rol(safe_add(safe_add(g,c),safe_add(b,e)),d),a)}function md5_ff(c,g,a,b,d,e,f){return md5_cmn(g&a|~g&b,c,g,d,e,f)}function md5_gg(c,g,a,b,d,e,f){return md5_cmn(g&b|a&~b,c,g,d,e,f)}function md5_hh(c,g,a,b,d,e,f){return md5_cmn(g^a^b,c,g,d,e,f)}function md5_ii(c,g,a,b,d,e,f){return md5_cmn(a^(g|~b),c,g,d,e,f)}function core_hmac_md5(c,g){var a=str2binl(c);16<a.length&&(a=core_md5(a,c.length*chrsz));for(var b=Array(16),d=Array(16),e=0;16>e;e++)b[e]=a[e]^909522486,d[e]=a[e]^1549556828;a=core_md5(b.concat(str2binl(g)),512+g.length*chrsz);return core_md5(d.concat(a),640)}function safe_add(c,g){var a=(c&65535)+(g&65535);return(c>>16)+(g>>16)+(a>>16)<<16|a&65535}function bit_rol(c,g){return c<<g|c>>>32-g}function str2binl(c){for(var g=[],a=(1<<chrsz)-1,b=0;b<c.length*chrsz;b+=chrsz)g[b>>5]|=(c.charCodeAt(b/chrsz)&a)<<b%32;return g}function binl2str(c){for(var g="",a=(1<<chrsz)-1,b=0;b<32*c.length;b+=chrsz)g+=String.fromCharCode(c[b>>5]>>>b%32&a);return g}function binl2hex(c){for(var g=hexcase?"0123456789ABCDEF":"0123456789abcdef",a="",b=0;b<4*c.length;b++)a+=g.charAt(c[b>>2]>>8*(b%4)+4&15)+g.charAt(c[b>>2]>>8*(b%4)&15);return a}function binl2b64(c){for(var g="",a=0;a<4*c.length;a+=3)for(var b=(c[a>>2]>>8*(a%4)&255)<<16|(c[a+1>>2]>>8*((a+1)%4)&255)<<8|c[a+2>>2]>>8*((a+2)%4)&255,d=0;4>d;d++)g=8*a+6*d>32*c.length?g+b64pad:g+"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/".charAt(b>>6*(3-d)&63);return g};