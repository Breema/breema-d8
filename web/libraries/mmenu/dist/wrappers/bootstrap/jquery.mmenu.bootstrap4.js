!function(a){function n(n){for(var e=n.is("a")?a("<a />"):a("<span />"),t=["href","title","target"],r=0;r<t.length;r++)void 0!==n.attr(t[r])&&e.attr(t[r],n.attr(t[r]));return e.html(n.html()),e.find(".sr-only").remove(),e}function e(e){var t=a("<ul />");return e.children().each(function(){var e=a(this),r=a("<li />");e.hasClass("dropdown-divider")?r.addClass("Divider"):e.hasClass("dropdown-item")&&r.append(n(e)),t.append(r)}),t}a.mmenu.wrappers.bootstrap4=function(){var t=this;if(this.$menu.hasClass("navbar-collapse")){this.conf.clone=!1;var r=a("<nav />"),i=a("<div />");r.append(i),this.$menu.children().each(function(){var r,s,o=a(this);switch(!0){case o.hasClass("navbar-nav"):i.append((r=o,s=a("<ul />"),r.find(".nav-item").each(function(){var t=a(this),r=a("<li />");if(t.hasClass("active")&&r.addClass("Selected"),!t.hasClass("nav-link")){var i=t.children(".dropdown-menu");i.length&&r.append(e(i)),t=t.children(".nav-link")}r.prepend(n(t)),s.append(r)}),s));break;case o.hasClass("dropdown-menu"):i.append(e(o));break;case o.hasClass("form-inline"):t.conf.searchfield.form={action:o.attr("action")||null,method:o.attr("method")||null},t.conf.searchfield.input={name:o.find("input").attr("name")||null},t.conf.searchfield.clear=!1,t.conf.searchfield.submit=!0;break;default:i.append(o.clone(!0))}}),this.bind("initMenu:before",function(){r.prependTo("body"),this.$menu=r}),this.$menu.parent().find(".navbar-toggler").removeAttr("data-target").removeAttr("aria-controls").off("click").on("click",function(a){a.preventDefault(),a.stopImmediatePropagation(),t[t.vars.opened?"close":"open"]()})}}}(jQuery);