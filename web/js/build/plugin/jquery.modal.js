/*
* $ lightbox_me
* By: Buck Wilson
* Version : 2.4
*
* Licensed under the Apache License, Version 2.0 (the "License");
* you may not use this file except in compliance with the License.
* You may obtain a copy of the License at
*
*     http://www.apache.org/licenses/LICENSE-2.0
*
* Unless required by applicable law or agreed to in writing, software
* distributed under the License is distributed on an "AS IS" BASIS,
* WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
* See the License for the specific language governing permissions and
* limitations under the License.
*/

!function(e){e.fn.lightbox_me=function(o){return console.log("lightbox_me"),this.each(function(){function n(){c[0].style;s.beforeClose(),s.destroyOnClose?c.add(d).remove():c.add(d).hide(),s.parentLightbox&&s.parentLightbox.fadeIn(200),s.preventScroll&&e("body").css("overflow",""),a.remove(),c.undelegate(s.closeSelector,"click"),c.unbind("close",n),c.unbind("repositon",l),e(window).unbind("resize",t),e(window).unbind("resize",l),e(window).unbind("scroll",l),e(window).unbind("keyup.lightbox_me"),s.onClose()}function i(e){(27==e.keyCode||27==e.DOM_VK_ESCAPE&&0==e.which)&&s.closeEsc&&n()}function t(){e(window).height()<e(document).height()?(d.css({height:e(document).height()+"px"}),a.css({height:e(document).height()+"px"})):d.css({height:"100%"})}function l(){c[0].style;if(c.css({left:"50%",marginLeft:c.outerWidth()/2*-1,zIndex:s.zIndex+3}),c.height()+80>=e(window).height()&&"absolute"!=c.css("position")){var o=e(document).scrollTop()+40;c.css({position:"absolute",top:o+"px",marginTop:0})}else c.height()+80<e(window).height()&&(s.centered?c.css({position:"fixed",top:"50%",marginTop:c.outerHeight()/2*-1}):c.css({position:"fixed"}).css(s.modalCSS),s.preventScroll&&e("body").css("overflow","hidden"))}var s=e.extend({},e.fn.lightbox_me.defaults,o),d=e(),c=e(this),a=e('<iframe id="foo" style="z-index: '+(s.zIndex+1)+';border: none; margin: 0; padding: 0; position: absolute; width: 100%; height: 100%; top: 0; left: 0; filter: mask();"/>');if(s.showOverlay){var r=e(".js_lb_overlay:visible");d=e(r.length>0?'<div class="lb_overlay_clear js_lb_overlay"/>':'<div class="'+s.classPrefix+'_overlay js_lb_overlay"/>')}e("body").append(c.hide()).append(d),s.showOverlay&&(t(),d.css({position:"absolute",width:"100%",top:0,left:0,right:0,bottom:0,zIndex:s.zIndex+2,display:"none"}),d.hasClass("lb_overlay_clear")||d.css(s.overlayCSS)),s.showOverlay?d.fadeIn(s.overlaySpeed,function(){l(),c[s.appearEffect](s.lightboxSpeed,function(){t(),l(),s.onLoad()})}):(l(),c[s.appearEffect](s.lightboxSpeed,function(){s.onLoad()})),s.parentLightbox&&s.parentLightbox.fadeOut(200),e(window).resize(t).resize(l).scroll(l),e(window).bind("keyup.lightbox_me",i),s.closeClick&&d.click(function(e){n(),e.preventDefault}),c.delegate(s.closeSelector,"click",function(e){n(),e.preventDefault()}),c.bind("close",n),c.bind("reposition",l)})},e.fn.lightbox_me.defaults={appearEffect:"fadeIn",appearEase:"",overlaySpeed:250,lightboxSpeed:300,closeSelector:".js-modal-close",closeClick:!0,closeEsc:!0,destroyOnClose:!0,showOverlay:!0,parentLightbox:!1,preventScroll:!1,onLoad:function(){},onClose:function(){},beforeClose:function(){},classPrefix:"lb",zIndex:1015,centered:!1,modalCSS:{top:"0"},overlayCSS:{background:"black",opacity:.3}}}(jQuery);