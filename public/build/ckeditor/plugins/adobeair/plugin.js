!function(){function e(e){for(var n=(e=e.getElementsByTag("*")).count(),o=0;o<n;o++)(function(e){for(var n=0;n<t.length;n++)!function(t){var n=e.getAttribute("on"+t);e.hasAttribute("on"+t)&&(e.removeAttribute("on"+t),e.on(t,(function(t){var o=(i=/(return\s*)?CKEDITOR\.tools\.callFunction\(([^)]+)\)/.exec(n))&&i[1],a=i&&i[2].split(","),i=/return false;/.test(n);if(a){for(var r,l=a.length,s=0;s<l;s++){a[s]=r=CKEDITOR.tools.trim(a[s]);var c=r.match(/^(["'])([^"']*?)\1$/);if(c)a[s]=c[2];else if(r.match(/\d+/))a[s]=parseInt(r,10);else switch(r){case"this":a[s]=e.$;break;case"event":a[s]=t.data.$;break;case"null":a[s]=null}}a=CKEDITOR.tools.callFunction.apply(window,a),o&&!1===a&&(i=1)}i&&t.data.preventDefault()})))}(t[n])})(e.getItem(o))}var t="click keydown mousedown keypress mouseover mouseout".split(" ");CKEDITOR.plugins.add("adobeair",{onLoad:function(){CKEDITOR.env.air&&(CKEDITOR.dom.document.prototype.write=CKEDITOR.tools.override(CKEDITOR.dom.document.prototype.write,(function(e){function t(e,t,n,o){t=e.append(t),(n=CKEDITOR.htmlParser.fragment.fromHtml(n).children[0].attributes)&&t.setAttributes(n),o&&t.append(e.getDocument().createText(o))}return function(n){if(this.getBody()){var o=this,a=this.getHead();n=n.replace(/(<style[^>]*>)([\s\S]*?)<\/style>/gi,(function(e,n,o){return t(a,"style",n,o),""})),n=(n=n.replace(/<base\b[^>]*\/>/i,(function(e){return t(a,"base",e),""}))).replace(/<title>([\s\S]*)<\/title>/i,(function(e,t){return o.$.title=t,""})),n=n.replace(/<head>([\s\S]*)<\/head>/i,(function(e){var t=new CKEDITOR.dom.element("div",o);return t.setHtml(e),t.moveChildren(a),""})),n.replace(/(<body[^>]*>)([\s\S]*)(?=$|<\/body>)/i,(function(e,t,n){o.getBody().setHtml(n),(e=CKEDITOR.htmlParser.fragment.fromHtml(t).children[0].attributes)&&o.getBody().setAttributes(e)}))}else e.apply(this,arguments)}})),CKEDITOR.addCss("body.cke_editable { padding: 8px }"),CKEDITOR.ui.on("ready",(function(t){if((t=t.data)._.panel){var n=t._.panel._.panel;!function t(){n.isLoaded?e(n._.holder):setTimeout(t,30)}()}else t instanceof CKEDITOR.dialog&&e(t._.element)})))},init:function(t){CKEDITOR.env.air&&(t.on("uiReady",(function(){e(t.container),t.on("elementsPathUpdate",(function(t){e(t.data.space)}))})),t.on("contentDom",(function(){t.document.on("click",(function(e){e.data.preventDefault(!0)}))})))}})}();