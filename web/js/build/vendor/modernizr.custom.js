window.Modernizr=function(e,t,n){function r(e){g.cssText=e}function o(e,t){return typeof e===t}function a(e,t){return!!~(""+e).indexOf(t)}function i(e,t){for(var r in e){var o=e[r];if(!a(o,"-")&&g[o]!==n)return"pfx"==t?o:!0}return!1}function c(e,t,r){for(var a in e){var i=t[e[a]];if(i!==n)return r===!1?e[a]:o(i,"function")?i.bind(r||t):i}return!1}function l(e,t,n){var r=e.charAt(0).toUpperCase()+e.slice(1),a=(e+" "+x.join(r+" ")+r).split(" ");return o(t,"string")||o(t,"undefined")?i(a,t):(a=(e+" "+C.join(r+" ")+r).split(" "),c(a,t,n))}var u,s,d,f="2.8.3",p={},m=!0,h=t.documentElement,y="modernizr",v=t.createElement(y),g=v.style,b=({}.toString," -webkit- -moz- -o- -ms- ".split(" ")),E="Webkit Moz O ms",x=E.split(" "),C=E.toLowerCase().split(" "),w={},T=[],S=T.slice,j=function(e,n,r,o){var a,i,c,l,u=t.createElement("div"),s=t.body,d=s||t.createElement("body");if(parseInt(r,10))for(;r--;)c=t.createElement("div"),c.id=o?o[r]:y+(r+1),u.appendChild(c);return a=["&#173;",'<style id="s',y,'">',e,"</style>"].join(""),u.id=y,(s?u:d).innerHTML+=a,d.appendChild(u),s||(d.style.background="",d.style.overflow="hidden",l=h.style.overflow,h.style.overflow="hidden",h.appendChild(d)),i=n(u,e),s?u.parentNode.removeChild(u):(d.parentNode.removeChild(d),h.style.overflow=l),!!i},P=function(t){var n=e.matchMedia||e.msMatchMedia;if(n)return n(t)&&n(t).matches||!1;var r;return j("@media "+t+" { #"+y+" { position: absolute; } }",function(t){r="absolute"==(e.getComputedStyle?getComputedStyle(t,null):t.currentStyle).position}),r},N=function(){function e(e,a){a=a||t.createElement(r[e]||"div"),e="on"+e;var i=e in a;return i||(a.setAttribute||(a=t.createElement("div")),a.setAttribute&&a.removeAttribute&&(a.setAttribute(e,""),i=o(a[e],"function"),o(a[e],"undefined")||(a[e]=n),a.removeAttribute(e))),a=null,i}var r={select:"input",change:"input",submit:"form",reset:"form",error:"img",load:"img",abort:"img"};return e}(),k={}.hasOwnProperty;d=o(k,"undefined")||o(k.call,"undefined")?function(e,t){return t in e&&o(e.constructor.prototype[t],"undefined")}:function(e,t){return k.call(e,t)},Function.prototype.bind||(Function.prototype.bind=function(e){var t=this;if("function"!=typeof t)throw new TypeError;var n=S.call(arguments,1),r=function(){if(this instanceof r){var o=function(){};o.prototype=t.prototype;var a=new o,i=t.apply(a,n.concat(S.call(arguments)));return Object(i)===i?i:a}return t.apply(e,n.concat(S.call(arguments)))};return r}),w.flexbox=function(){return l("flexWrap")},w.flexboxlegacy=function(){return l("boxDirection")},w.canvas=function(){var e=t.createElement("canvas");return!(!e.getContext||!e.getContext("2d"))},w.canvastext=function(){return!(!p.canvas||!o(t.createElement("canvas").getContext("2d").fillText,"function"))},w.touch=function(){var n;return"ontouchstart"in e||e.DocumentTouch&&t instanceof DocumentTouch?n=!0:j(["@media (",b.join("touch-enabled),("),y,")","{#modernizr{top:9px;position:absolute}}"].join(""),function(e){n=9===e.offsetTop}),n},w.multiplebgs=function(){return r("background:url(https://),url(https://),red url(https://)"),/(url\s*\(.*?){3}/.test(g.background)},w.backgroundsize=function(){return l("backgroundSize")},w.cssanimations=function(){return l("animationName")},w.csscolumns=function(){return l("columnCount")},w.video=function(){var e=t.createElement("video"),n=!1;try{(n=!!e.canPlayType)&&(n=new Boolean(n),n.ogg=e.canPlayType('video/ogg; codecs="theora"').replace(/^no$/,""),n.h264=e.canPlayType('video/mp4; codecs="avc1.42E01E"').replace(/^no$/,""),n.webm=e.canPlayType('video/webm; codecs="vp8, vorbis"').replace(/^no$/,""))}catch(r){}return n},w.audio=function(){var e=t.createElement("audio"),n=!1;try{(n=!!e.canPlayType)&&(n=new Boolean(n),n.ogg=e.canPlayType('audio/ogg; codecs="vorbis"').replace(/^no$/,""),n.mp3=e.canPlayType("audio/mpeg;").replace(/^no$/,""),n.wav=e.canPlayType('audio/wav; codecs="1"').replace(/^no$/,""),n.m4a=(e.canPlayType("audio/x-m4a;")||e.canPlayType("audio/aac;")).replace(/^no$/,""))}catch(r){}return n};for(var M in w)d(w,M)&&(s=M.toLowerCase(),p[s]=w[M](),T.push((p[s]?"":"no-")+s));return p.addTest=function(e,t){if("object"==typeof e)for(var r in e)d(e,r)&&p.addTest(r,e[r]);else{if(e=e.toLowerCase(),p[e]!==n)return p;t="function"==typeof t?t():t,"undefined"!=typeof m&&m&&(h.className+=" "+(t?"":"no-")+e),p[e]=t}return p},r(""),v=u=null,function(e,t){function n(e,t){var n=e.createElement("p"),r=e.getElementsByTagName("head")[0]||e.documentElement;return n.innerHTML="x<style>"+t+"</style>",r.insertBefore(n.lastChild,r.firstChild)}function r(){var e=g.elements;return"string"==typeof e?e.split(" "):e}function o(e){var t=v[e[h]];return t||(t={},y++,e[h]=y,v[y]=t),t}function a(e,n,r){if(n||(n=t),s)return n.createElement(e);r||(r=o(n));var a;return a=r.cache[e]?r.cache[e].cloneNode():m.test(e)?(r.cache[e]=r.createElem(e)).cloneNode():r.createElem(e),!a.canHaveChildren||p.test(e)||a.tagUrn?a:r.frag.appendChild(a)}function i(e,n){if(e||(e=t),s)return e.createDocumentFragment();n=n||o(e);for(var a=n.frag.cloneNode(),i=0,c=r(),l=c.length;l>i;i++)a.createElement(c[i]);return a}function c(e,t){t.cache||(t.cache={},t.createElem=e.createElement,t.createFrag=e.createDocumentFragment,t.frag=t.createFrag()),e.createElement=function(n){return g.shivMethods?a(n,e,t):t.createElem(n)},e.createDocumentFragment=Function("h,f","return function(){var n=f.cloneNode(),c=n.createElement;h.shivMethods&&("+r().join().replace(/[\w\-]+/g,function(e){return t.createElem(e),t.frag.createElement(e),'c("'+e+'")'})+");return n}")(g,t.frag)}function l(e){e||(e=t);var r=o(e);return!g.shivCSS||u||r.hasCSS||(r.hasCSS=!!n(e,"article,aside,dialog,figcaption,figure,footer,header,hgroup,main,nav,section{display:block}mark{background:#FF0;color:#000}template{display:none}")),s||c(e,r),e}var u,s,d="3.7.0",f=e.html5||{},p=/^<|^(?:button|map|select|textarea|object|iframe|option|optgroup)$/i,m=/^(?:a|b|code|div|fieldset|h1|h2|h3|h4|h5|h6|i|label|li|ol|p|q|span|strong|style|table|tbody|td|th|tr|ul)$/i,h="_html5shiv",y=0,v={};!function(){try{var e=t.createElement("a");e.innerHTML="<xyz></xyz>",u="hidden"in e,s=1==e.childNodes.length||function(){t.createElement("a");var e=t.createDocumentFragment();return"undefined"==typeof e.cloneNode||"undefined"==typeof e.createDocumentFragment||"undefined"==typeof e.createElement}()}catch(n){u=!0,s=!0}}();var g={elements:f.elements||"abbr article aside audio bdi canvas data datalist details dialog figcaption figure footer header hgroup main mark meter nav output progress section summary template time video",version:d,shivCSS:f.shivCSS!==!1,supportsUnknownElements:s,shivMethods:f.shivMethods!==!1,type:"default",shivDocument:l,createElement:a,createDocumentFragment:i};e.html5=g,l(t)}(this,t),p._version=f,p._prefixes=b,p._domPrefixes=C,p._cssomPrefixes=x,p.mq=P,p.hasEvent=N,p.testProp=function(e){return i([e])},p.testAllProps=l,p.testStyles=j,h.className=h.className.replace(/(^|\s)no-js(\s|$)/,"$1$2")+(m?" js "+T.join(" "):""),p}(this,this.document),function(e,t,n){function r(e){return"[object Function]"==y.call(e)}function o(e){return"string"==typeof e}function a(){}function i(e){return!e||"loaded"==e||"complete"==e||"uninitialized"==e}function c(){var e=v.shift();g=1,e?e.t?m(function(){("c"==e.t?f.injectCss:f.injectJs)(e.s,0,e.a,e.x,e.e,1)},0):(e(),c()):g=0}function l(e,n,r,o,a,l,u){function s(t){if(!p&&i(d.readyState)&&(b.r=p=1,!g&&c(),d.onload=d.onreadystatechange=null,t)){"img"!=e&&m(function(){x.removeChild(d)},50);for(var r in j[n])j[n].hasOwnProperty(r)&&j[n][r].onload()}}var u=u||f.errorTimeout,d=t.createElement(e),p=0,y=0,b={t:r,s:n,e:a,a:l,x:u};1===j[n]&&(y=1,j[n]=[]),"object"==e?d.data=n:(d.src=n,d.type=e),d.width=d.height="0",d.onerror=d.onload=d.onreadystatechange=function(){s.call(this,y)},v.splice(o,0,b),"img"!=e&&(y||2===j[n]?(x.insertBefore(d,E?null:h),m(s,u)):j[n].push(d))}function u(e,t,n,r,a){return g=0,t=t||"j",o(e)?l("c"==t?w:C,e,t,this.i++,n,r,a):(v.splice(this.i++,0,e),1==v.length&&c()),this}function s(){var e=f;return e.loader={load:u,i:0},e}var d,f,p=t.documentElement,m=e.setTimeout,h=t.getElementsByTagName("script")[0],y={}.toString,v=[],g=0,b="MozAppearance"in p.style,E=b&&!!t.createRange().compareNode,x=E?p:h.parentNode,p=e.opera&&"[object Opera]"==y.call(e.opera),p=!!t.attachEvent&&!p,C=b?"object":p?"script":"img",w=p?"script":C,T=Array.isArray||function(e){return"[object Array]"==y.call(e)},S=[],j={},P={timeout:function(e,t){return t.length&&(e.timeout=t[0]),e}};f=function(e){function t(e){var t,n,r,e=e.split("!"),o=S.length,a=e.pop(),i=e.length,a={url:a,origUrl:a,prefixes:e};for(n=0;i>n;n++)r=e[n].split("="),(t=P[r.shift()])&&(a=t(a,r));for(n=0;o>n;n++)a=S[n](a);return a}function i(e,o,a,i,c){var l=t(e),u=l.autoCallback;l.url.split(".").pop().split("?").shift(),l.bypass||(o&&(o=r(o)?o:o[e]||o[i]||o[e.split("/").pop().split("?")[0]]),l.instead?l.instead(e,o,a,i,c):(j[l.url]?l.noexec=!0:j[l.url]=1,a.load(l.url,l.forceCSS||!l.forceJS&&"css"==l.url.split(".").pop().split("?").shift()?"c":n,l.noexec,l.attrs,l.timeout),(r(o)||r(u))&&a.load(function(){s(),o&&o(l.origUrl,c,i),u&&u(l.origUrl,c,i),j[l.url]=2})))}function c(e,t){function n(e,n){if(e){if(o(e))n||(d=function(){var e=[].slice.call(arguments);f.apply(this,e),p()}),i(e,d,t,0,u);else if(Object(e)===e)for(l in c=function(){var t,n=0;for(t in e)e.hasOwnProperty(t)&&n++;return n}(),e)e.hasOwnProperty(l)&&(!n&&!--c&&(r(d)?d=function(){var e=[].slice.call(arguments);f.apply(this,e),p()}:d[l]=function(e){return function(){var t=[].slice.call(arguments);e&&e.apply(this,t),p()}}(f[l])),i(e[l],d,t,l,u))}else!n&&p()}var c,l,u=!!e.test,s=e.load||e.both,d=e.callback||a,f=d,p=e.complete||a;n(u?e.yep:e.nope,!!s),s&&n(s)}var l,u,d=this.yepnope.loader;if(o(e))i(e,0,d,0);else if(T(e))for(l=0;l<e.length;l++)u=e[l],o(u)?i(u,0,d,0):T(u)?f(u):Object(u)===u&&c(u,d);else Object(e)===e&&c(e,d)},f.addPrefix=function(e,t){P[e]=t},f.addFilter=function(e){S.push(e)},f.errorTimeout=1e4,null==t.readyState&&t.addEventListener&&(t.readyState="loading",t.addEventListener("DOMContentLoaded",d=function(){t.removeEventListener("DOMContentLoaded",d,0),t.readyState="complete"},0)),e.yepnope=s(),e.yepnope.executeStack=c,e.yepnope.injectJs=function(e,n,r,o,l,u){var s,d,p=t.createElement("script"),o=o||f.errorTimeout;p.src=e;for(d in r)p.setAttribute(d,r[d]);n=u?c:n||a,p.onreadystatechange=p.onload=function(){!s&&i(p.readyState)&&(s=1,n(),p.onload=p.onreadystatechange=null)},m(function(){s||(s=1,n(1))},o),l?p.onload():h.parentNode.insertBefore(p,h)},e.yepnope.injectCss=function(e,n,r,o,i,l){var u,o=t.createElement("link"),n=l?c:n||a;o.href=e,o.rel="stylesheet",o.type="text/css";for(u in r)o.setAttribute(u,r[u]);i||(h.parentNode.insertBefore(o,h),m(n,0))}}(this,document),Modernizr.load=function(){yepnope.apply(window,[].slice.call(arguments,0))};