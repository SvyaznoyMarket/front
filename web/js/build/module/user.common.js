define(["jquery","underscore","module/config"],function(e,r,t){var s=e("body");t.user.infoUrl&&e.post(t.user.infoUrl).done(function(e){r.isObject(e.result)&&(r.isObject(e.result.widgets)&&(s.data("widget",e.result.widgets),s.trigger("render")),r.isObject(e.result.user)&&s.data("user",e.result.user),r.isObject(e.result.cart)&&s.data("cart",e.result.cart))})});