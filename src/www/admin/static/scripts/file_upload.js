(function () {
	if (!FileReader || !File)
		return false;

	var uploadHelper = function ()
	{
		var rusha = new Rusha();
		var form = $('#f_upload');
		var max_size = $('#f_maxsize').value;
		var admin_url = g.admin_url;

		form.onsubmit = function () {
			return true;
		};

		$('#f_fichier').onchange = function () {
			if (this.files.length < 1)
				return false;

			if (this.files.length > 1)
				return !alert("Vous ne pouvez sélectionner qu'un seul fichier.");

			var file = this.files[0];

			if (file.size > max_size)
			{
				this.value = '';
				return !alert("Ce fichier de " + getByteSize(file.size) 
					+ " dépasse la taille autorisée de " + getByteSize(max_size) + ".\nEnvoi impossible !");
			}

			var name = file.name.replace(/\.[^.]+/g, '');
			name = name.replace(/[_.-]+/g, ' ');
			name = name.replace(/\w/, function (match) { return match.toUpperCase(); });

			document.getElementById('f_titre').value = name;

			// Vérification de l'existence du fichier
			var fr = new FileReader;
			fr.onloadend = function () {
				if (this.error) return false;
				var hash = rusha.digestFromArrayBuffer(fr.result);
				garradin.load(garradin.admin_url + '_upload_check.php?hash=' + hash, function (data) {
					if (parseInt(data, 10) == 1)
					{
						alert('ok');
					}
				})
			};
			fr.readAsArrayBuffer(file);
		};
	};

	function getByteSize(size)
	{
		if (size < 1024)
			return size + ' octets';
		else if (size < 1024*1024)
			return Math.round(size / 1024) + ' Ko';
		else
			return (Math.round(size / 1024 / 1024 * 100) / 100) + ' Mo';
	}

	garradin.onload(uploadHelper);
}());


/*! rusha 2015-01-11 */
!function(){function a(a){"use strict";var d={fill:0},f=function(a){for(a+=9;a%64>0;a+=1);return a},g=function(a,b){for(var c=b>>2;c<a.length;c++)a[c]=0},h=function(a,b,c){a[b>>2]|=128<<24-(b%4<<3),a[((b>>2)+2&-16)+15]=c<<3},i=function(a,b,c,d,e){var f,g=this,h=e%4,i=d%4,j=d-i;if(j>0)switch(h){case 0:a[e+3|0]=g.charCodeAt(c);case 1:a[e+2|0]=g.charCodeAt(c+1);case 2:a[e+1|0]=g.charCodeAt(c+2);case 3:a[0|e]=g.charCodeAt(c+3)}for(f=h;j>f;f=f+4|0)b[e+f>>2]=g.charCodeAt(c+f)<<24|g.charCodeAt(c+f+1)<<16|g.charCodeAt(c+f+2)<<8|g.charCodeAt(c+f+3);switch(i){case 3:a[e+j+1|0]=g.charCodeAt(c+j+2);case 2:a[e+j+2|0]=g.charCodeAt(c+j+1);case 1:a[e+j+3|0]=g.charCodeAt(c+j)}},j=function(a,b,c,d,e){var f,g=this,h=e%4,i=d%4,j=d-i;if(j>0)switch(h){case 0:a[e+3|0]=g[c];case 1:a[e+2|0]=g[c+1];case 2:a[e+1|0]=g[c+2];case 3:a[0|e]=g[c+3]}for(f=4-h;j>f;f=f+=4)b[e+f>>2]=g[c+f]<<24|g[c+f+1]<<16|g[c+f+2]<<8|g[c+f+3];switch(i){case 3:a[e+j+1|0]=g[c+j+2];case 2:a[e+j+2|0]=g[c+j+1];case 1:a[e+j+3|0]=g[c+j]}},k=function(a,b,d,e,f){var g,h=this,i=f%4,j=e%4,k=e-j,l=new Uint8Array(c.readAsArrayBuffer(h.slice(d,d+e)));if(k>0)switch(i){case 0:a[f+3|0]=l[0];case 1:a[f+2|0]=l[1];case 2:a[f+1|0]=l[2];case 3:a[0|f]=l[3]}for(g=4-i;k>g;g=g+=4)b[f+g>>2]=l[g]<<24|l[g+1]<<16|l[g+2]<<8|l[g+3];switch(j){case 3:a[f+k+1|0]=l[k+2];case 2:a[f+k+2|0]=l[k+1];case 1:a[f+k+3|0]=l[k]}},l=function(a){switch(e.getDataType(a)){case"string":return i.bind(a);case"array":return j.bind(a);case"buffer":return j.bind(a);case"arraybuffer":return j.bind(new Uint8Array(a));case"view":return j.bind(new Uint8Array(a.buffer,a.byteOffset,a.byteLength));case"blob":return k.bind(a)}},m=function(a){var b,c,d="0123456789abcdef",e=[],f=new Uint8Array(a);for(b=0;b<f.length;b++)c=f[b],e[b]=d.charAt(c>>4&15)+d.charAt(c>>0&15);return e.join("")},n=function(a){var b;if(65536>=a)return 65536;if(16777216>a)for(b=1;a>b;b<<=1);else for(b=16777216;a>b;b+=16777216);return b},o=function(a){if(a%64>0)throw new Error("Chunk size must be a multiple of 128 bit");d.maxChunkLen=a,d.padMaxChunkLen=f(a),d.heap=new ArrayBuffer(n(d.padMaxChunkLen+320+20)),d.h32=new Int32Array(d.heap),d.h8=new Int8Array(d.heap),d.core=b({Int32Array:Int32Array,DataView:DataView},{},d.heap),d.buffer=null};o(a||65536);var p=function(a,b){var c=new Int32Array(a,b+320,5);c[0]=1732584193,c[1]=-271733879,c[2]=-1732584194,c[3]=271733878,c[4]=-1009589776},q=function(a,b){var c=f(a),e=new Int32Array(d.heap,0,c>>2);return g(e,a),h(e,a,b),c},r=function(a,b,c){l(a)(d.h8,d.h32,b,c,0)},s=function(a,b,c,e,f){var g=c;f&&(g=q(c,e)),r(a,b,c),d.core.hash(g,d.padMaxChunkLen)},t=function(a,b){var c=new Int32Array(a,b+320,5),d=new Int32Array(5),e=new DataView(d.buffer);return e.setInt32(0,c[0],!1),e.setInt32(4,c[1],!1),e.setInt32(8,c[2],!1),e.setInt32(12,c[3],!1),e.setInt32(16,c[4],!1),d},u=this.rawDigest=function(a){var b=a.byteLength||a.length||a.size;p(d.heap,d.padMaxChunkLen);var c=0,e=d.maxChunkLen;for(c=0;b>c+e;c+=e)s(a,c,e,b,!1);return s(a,c,b-c,b,!0),t(d.heap,d.padMaxChunkLen)};this.digest=this.digestFromString=this.digestFromBuffer=this.digestFromArrayBuffer=function(a){return m(u(a).buffer)}}function b(a,b,c){"use asm";function d(a,b){a|=0,b|=0;var c=0,d=0,f=0,g=0,h=0,i=0,j=0,k=0,l=0,m=0,n=0,o=0,p=0,q=0;for(f=e[b+320>>2]|0,h=e[b+324>>2]|0,j=e[b+328>>2]|0,l=e[b+332>>2]|0,n=e[b+336>>2]|0,c=0;(c|0)<(a|0);c=c+64|0){for(g=f,i=h,k=j,m=l,o=n,d=0;(d|0)<64;d=d+4|0)q=e[c+d>>2]|0,p=((f<<5|f>>>27)+(h&j|~h&l)|0)+((q+n|0)+1518500249|0)|0,n=l,l=j,j=h<<30|h>>>2,h=f,f=p,e[a+d>>2]=q;for(d=a+64|0;(d|0)<(a+80|0);d=d+4|0)q=(e[d-12>>2]^e[d-32>>2]^e[d-56>>2]^e[d-64>>2])<<1|(e[d-12>>2]^e[d-32>>2]^e[d-56>>2]^e[d-64>>2])>>>31,p=((f<<5|f>>>27)+(h&j|~h&l)|0)+((q+n|0)+1518500249|0)|0,n=l,l=j,j=h<<30|h>>>2,h=f,f=p,e[d>>2]=q;for(d=a+80|0;(d|0)<(a+160|0);d=d+4|0)q=(e[d-12>>2]^e[d-32>>2]^e[d-56>>2]^e[d-64>>2])<<1|(e[d-12>>2]^e[d-32>>2]^e[d-56>>2]^e[d-64>>2])>>>31,p=((f<<5|f>>>27)+(h^j^l)|0)+((q+n|0)+1859775393|0)|0,n=l,l=j,j=h<<30|h>>>2,h=f,f=p,e[d>>2]=q;for(d=a+160|0;(d|0)<(a+240|0);d=d+4|0)q=(e[d-12>>2]^e[d-32>>2]^e[d-56>>2]^e[d-64>>2])<<1|(e[d-12>>2]^e[d-32>>2]^e[d-56>>2]^e[d-64>>2])>>>31,p=((f<<5|f>>>27)+(h&j|h&l|j&l)|0)+((q+n|0)-1894007588|0)|0,n=l,l=j,j=h<<30|h>>>2,h=f,f=p,e[d>>2]=q;for(d=a+240|0;(d|0)<(a+320|0);d=d+4|0)q=(e[d-12>>2]^e[d-32>>2]^e[d-56>>2]^e[d-64>>2])<<1|(e[d-12>>2]^e[d-32>>2]^e[d-56>>2]^e[d-64>>2])>>>31,p=((f<<5|f>>>27)+(h^j^l)|0)+((q+n|0)-899497514|0)|0,n=l,l=j,j=h<<30|h>>>2,h=f,f=p,e[d>>2]=q;f=f+g|0,h=h+i|0,j=j+k|0,l=l+m|0,n=n+o|0}e[b+320>>2]=f,e[b+324>>2]=h,e[b+328>>2]=j,e[b+332>>2]=l,e[b+336>>2]=n}var e=new a.Int32Array(c);return{hash:d}}if("undefined"!=typeof module?module.exports=a:"undefined"!=typeof window&&(window.Rusha=a),"undefined"!=typeof FileReaderSync){var c=new FileReaderSync,d=new a(4194304);self.onmessage=function(a){var b,c=a.data.data;try{b=d.digest(c),self.postMessage({id:a.data.id,hash:b})}catch(e){self.postMessage({id:a.data.id,error:e.name})}}}var e={getDataType:function(a){if("string"==typeof a)return"string";if(a instanceof Array)return"array";if("undefined"!=typeof global&&global.Buffer&&global.Buffer.isBuffer(a))return"buffer";if(a instanceof ArrayBuffer)return"arraybuffer";if(a.buffer instanceof ArrayBuffer)return"view";if(a instanceof Blob)return"blob";throw new Error("Unsupported data type.")}}}();