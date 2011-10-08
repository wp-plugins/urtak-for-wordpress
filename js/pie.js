(function ($) {
  Raphael.fn.pie = function (cx, cy, r, values, colors, color_stroke, fEnter, fLeave, fClick) {
    var paper = this,
    rad = Math.PI / 180,
    chart = this.set(),
    angle = 90,
    total = 0,
    start = 0,
    i, ii;

    function sector(cx, cy, r, startAngle, endAngle, params) {
      var x1 = cx + r * Math.cos(-startAngle * rad),
      x2 = cx + r * Math.cos(-endAngle * rad),
      y1 = cy + r * Math.sin(-startAngle * rad),
      y2 = cy + r * Math.sin(-endAngle * rad);
      return paper.path([
        "M", cx, cy, "L", x1, y1, "A", r, r, 0, + (endAngle - startAngle > 180), 0, x2, y2, "z"
      ]).attr(params);
    }

    function process(j) {
      var value = values[j],
      angleplus = 360 * value / total,
      popangle = angle + (angleplus / 2),
      color = colors[j],
      ms = 500,
      delta = 30,
      params = {fill : color, stroke : color_stroke, "stroke-width" : 1},
      p;
      if (angleplus === 360) {
        p = paper.circle(cx, cy, r).attr(params);
      } else if (angleplus !== 0) {
        p = sector(cx, cy, r, angle, angle + angleplus, params);
      }
      angle += angleplus;
      chart.push(p);
      start += 0.1;
    }

    for (i = 0, ii = values.length; i < ii; i += 1) {
      total += values[i];
    }

    for (i = 0; i < ii; i += 1) {
      process(i);
    }

    if (fEnter) {
      chart.mouseover($.proxy(fEnter, paper.canvas.parentElement));
    }

    if (fLeave) {
      chart.mouseout($.proxy(fLeave, paper.canvas.parentElement));
    }

    if (fClick) {
      chart.click($.proxy(fClick, paper.canvas.parentElement));
    }

    // If we are dealing with P.O.S. IE...
    if ($.browser.msie && paper.canvas.parentElement && paper.canvas.parentElement.fireEvent) {
      $.each(["mouseover", "mouseout", "click", "mousedown", "mouseup"], function (i, e_type) {
        var e_type_fire;
        if (e_type === "mouseover") {
          e_type_fire = "onMouseOver";
        } else if (e_type === "mouseout") {
          e_type_fire = "onMouseOut";
        }
        // Not necessary now so let's leave these out...
        // } else if (e_type === "click") {
        //   e_type_fire = "click";
        // } else if (e_type === "mousedown") {
        //   e_type_fire = "onMouseDown";
        // } else if (e_type === "mouseup") {
        //   e_type_fire = "onMouseUp";
        // }
        chart[e_type](function (e) {
          if (e_type_fire) {
            paper.canvas.parentElement.fireEvent(e_type_fire);
          }
          e.stopPropagation();
        });
      });
    }

    return chart;
  };

  $.fn.pie = function (size, fEnter, fLeave, fClick) {
    return $(this).each(function () {
      var $this = $(this);
      if (!size) {
        size = $this.height() > $this.width() ? $this.width() : $this.height();
      }
      if (size > 0) {
        if ($this.data("pie-count-care") && $this.data("pie-count-care") > 0) {
          (new Raphael(this, size, size)).pie(
            size/2,
            size/2,
            size/2,
            [$this.data("pie-percent-no"), $this.data("pie-percent-yes")],
            ["#ff7100", "#00aef0"],
            "#FFFFFF",
            fEnter,
            fLeave,
            fClick);
        } else {
          (new Raphael(this, size, size)).pie(
            size/2 + 1,
            size/2 + 1,
            size/2 - 2,
            [100],
            ["#F8F8F8"],
            "#000",
            fEnter,
            fLeave,
            fClick);
        }
      }
    });
  };
})(jQuery);

