!function(){function e(e){var t=this instanceof CKEDITOR.ui.dialog.checkbox;e.hasAttribute(this.id)&&(e=e.getAttribute(this.id),t?this.setValue(i[this.id].true==e.toLowerCase()):this.setValue(e))}function t(e){var t=this.getValue(),a=this.att||this.id,l=this instanceof CKEDITOR.ui.dialog.checkbox?i[this.id][t]:t;""===t||"tabindex"===a&&!1===t?e.removeAttribute(a):e.setAttribute(a,l)}var i={scrolling:{true:"yes",false:"no"},frameborder:{true:"1",false:"0"},tabindex:{true:"-1",false:!1}};CKEDITOR.dialog.add("iframe",(function(i){var a=i.lang.iframe,l=i.lang.common,n=i.plugins.dialogadvtab;return{title:a.title,minWidth:350,minHeight:260,getModel:function(e){return(e=e.getSelection().getSelectedElement())&&"iframe"===e.data("cke-real-element-type")?e:null},onShow:function(){this.fakeImage=this.iframeNode=null;var e=this.getSelectedElement();e&&e.data("cke-real-element-type")&&"iframe"==e.data("cke-real-element-type")&&(this.fakeImage=e,this.iframeNode=e=i.restoreRealElement(e),this.setupContent(e))},onOk:function(){var e;e=this.fakeImage?this.iframeNode:new CKEDITOR.dom.element("iframe");var t={},a={};this.commitContent(e,t,a);var l=i.plugins.iframe._.getIframeAttributes(i,e);e.setAttributes(l),(e=i.createFakeElement(e,"cke_iframe","iframe",!0)).setAttributes(a),e.setStyles(t),this.fakeImage?(e.replace(this.fakeImage),i.getSelection().selectElement(e)):i.insertElement(e)},contents:[{id:"info",label:l.generalTab,accessKey:"I",elements:[{type:"vbox",padding:0,children:[{id:"src",type:"text",label:l.url,required:!0,validate:CKEDITOR.dialog.validate.notEmpty(a.noUrl),setup:e,commit:t}]},{type:"hbox",children:[{id:"width",type:"text",requiredContent:"iframe[width]",style:"width:100%",labelLayout:"vertical",label:l.width,validate:CKEDITOR.dialog.validate.htmlLength(l.invalidHtmlLength.replace("%1",l.width)),setup:e,commit:t},{id:"height",type:"text",requiredContent:"iframe[height]",style:"width:100%",labelLayout:"vertical",label:l.height,validate:CKEDITOR.dialog.validate.htmlLength(l.invalidHtmlLength.replace("%1",l.height)),setup:e,commit:t},{id:"align",type:"select",requiredContent:"iframe[align]",default:"",items:[[l.notSet,""],[l.left,"left"],[l.right,"right"],[l.alignTop,"top"],[l.alignMiddle,"middle"],[l.alignBottom,"bottom"]],style:"width:100%",labelLayout:"vertical",label:l.align,setup:function(t,i){if(e.apply(this,arguments),i){var a=i.getAttribute("align");this.setValue(a&&a.toLowerCase()||"")}},commit:function(e,i,a){t.apply(this,arguments),this.getValue()&&(a.align=this.getValue())}}]},{type:"hbox",widths:["33%","33%","33%"],children:[{id:"scrolling",type:"checkbox",requiredContent:"iframe[scrolling]",label:a.scrolling,setup:e,commit:t},{id:"frameborder",type:"checkbox",requiredContent:"iframe[frameborder]",label:a.border,setup:e,commit:t},{id:"tabindex",type:"checkbox",requiredContent:"iframe[tabindex]",label:a.tabindex,setup:e,commit:t}]},{type:"hbox",widths:["50%","50%"],children:[{id:"name",type:"text",requiredContent:"iframe[name]",label:l.name,setup:e,commit:t},{id:"title",type:"text",requiredContent:"iframe[title]",label:l.advisoryTitle,setup:e,commit:t}]},{id:"longdesc",type:"text",requiredContent:"iframe[longdesc]",label:l.longDescr,setup:e,commit:t}]},n&&n.createAdvancedTab(i,{id:1,classes:1,styles:1},"iframe")]}}))}();