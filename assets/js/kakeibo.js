require('../css/kakeibo.scss');

// Add ChartJS
var ChartJS = require('chart.js');

var kakeibo = {
  // Viewport sizes
  viewport: {
    kb: null,
    $sizes: null,
    last_call_size: null,
    init: function(kb) {
      this.kb     = kb;
      this.$sizes = this.kb.$body.find('.viewport-sizes > *');
      this.last_call_size = this.$sizes.filter(':visible').data('viewport-size-slug');
    },
    current: function() {
      return this.$sizes.filter(':visible').data('viewport-size-slug');
    },
    has_change: function() {
      var new_size = this.$sizes.filter(':visible').data('viewport-size-slug');
      var has_change = new_size != this.last_call_size;

      this.last_call_size = new_size;

      return has_change;
    }
  },
  // ChartJS
  chartJS: {
    kb: null,
    $items: null,
    is_legend_visible: function(hide_sizes) {
      var show = true;
      for (var i = 0; i < hide_sizes.length; i++) {
        if (kakeibo.viewport.current() == hide_sizes[i]) {
          show = false;
          break;
        }
      }
      return show;
    },
    init: function(kb) {
      var self    = this;
      this.kb     = kb;
      this.$items = this.kb.$body.find('.chart-js');

      this.$items.each(function() {
        var $chart = $(this),
            canvas = $chart.find('canvas')
            data_name   = $chart.data('chartjs-data-name'),
            data_type   = $chart.data('chartjs-data-type'),
            chart_type  = $chart.data('chartjs-type'),
            chart_min   = $chart.data('chartjs-min'),
            chart_max   = $chart.data('chartjs-max'),
            legend_display    = (typeof $chart.data('chartjs-legend-display') != 'undefined') ? $chart.data('chartjs-legend-display') : true
            legend_position   = $chart.data('chartjs-legend-position')
            legend_hide_sizes = (typeof $chart.data('chartjs-legend-hide') == 'string') ? $chart.data('chartjs-legend-hide').split('|') : [];

        if (typeof data_name != 'undefined' && typeof window[data_name] != 'undefined') {
          var opts = {};
          var data = window[data_name];

          if (typeof chart_min != 'undefined' && typeof chart_max != 'undefined') {
            opts.scale = {
              ticks : {
                min : chart_min,
                max : chart_max
              }
            };
          }

          // Chart legend
          if (legend_display)
            legend_display = self.is_legend_visible(legend_hide_sizes);

          opts.legend = {
            display: legend_display
          };

          if (typeof legend_position != 'undefined')
            opts.legend.position = legend_position;

          // Custom tooltips
          if (typeof data_type != 'undefined') {
            opts.tooltips = {
              callbacks: {
                label: function(tooltipItem, data) {
                  // Add % character to percent data type values
                  if (data_type == 'percent') {
                    var label = tooltipItem.value + '%';
                    // Push counting things for percent values
                    var datasets = data.datasets[tooltipItem.datasetIndex];
                    if (typeof datasets != 'undefined' && typeof datasets.data_count != 'undefined') {
                      label += ' (' + datasets.data_count[tooltipItem.index] + ' ' + datasets.label + ((datasets.data_count[tooltipItem.index] > 1) ? 's': '') + ')';
                    }

                    return label;
                  }
                }
              }
            };
          }

          var chartJS = new Chart(canvas, {
            type : chart_type,
            data : data,
            options : opts,
            plugins: [{
              resize: function() {
                $chart.data('chartJS').options.legend.display = self.is_legend_visible(legend_hide_sizes);
                $chart.data('chartJS').update();
              }
            }]
          });

          $chart.data('chartJS', chartJS);
        }
      });

      // Fix for printing (NOTE: but doesn't work...)
      function beforePrintHandler () {
        for (var id in Chart.instances) {
          Chart.instances[id].resize();
        }
      }
      window.addEventListener("beforeprint", beforePrintHandler);
    }
  },
  // Transaction panel (add/edit transac.)
  $panel_transaction : null,
  toggle_panel_transaction : function(type) {
    type = (typeof type == 'undefined') ? 'add' : type;

    console.log('kakeibo.toggle_panel_transaction(' + type + ')');

    if (this.$panel_transaction != null && this.$panel_transaction.length > 0) {
      // console.log('type: ' + type);
      // console.log(this.$panel_transaction);
      // if (this.$panel_transaction.hasClass('app-panel--hide')) {
      //
      // }
      this.$body.toggleClass('app-panel--transaction-form--show');
      // TODO : load transaction data when type = "edit"
    } else {
      console.warn('Must define a valid kakeibo.$panel_transaction');
    }
  },
  // Loader
  is_loading    : false,
  loading_class : 'app-core--is-loading',
  load : function() {
    this.is_loading = true;
    this.$body.addClass(this.loading_class);
  },
  unload : function() {
    this.is_loading = false;
    this.$body.removeClass(this.loading_class);
  },
  // = ~ init/construct
  launch: function() {
    // Nodes
    this.$body    = $('body');
    this.$window  = $(window);

    this.load();

    // Viewport
    this.viewport.init(this);

    // Transactions
    this.$panel_transaction = this.$body.find('.app-panel--transaction-form');

    // Charts
    this.chartJS.init(this);
  }
};

// Rocket launcher !
kakeibo.launch();

// On doc ready
(function() {
  console.log("Hi ! I'm kakeibo.js");

  // ====================================
  // EVENTS / TRANSACTIONS ==============
  kakeibo.$body.on('click', '.toggle-panel-transaction', function() {
    kakeibo.toggle_panel_transaction($(this).data('panel-type'));
  });
  kakeibo.$trans_form_import = kakeibo.$body.find('.form-trans-import');
  kakeibo.$trans_form_import.on('change', '.first-import-file', function() {
    kakeibo.$trans_form_import.submit();
  });

  // ====================================
  // EVENTS / TOGGLER ===================
  kakeibo.$body.on('click', '[data-toggler-class]', function(e) {
    var $this = $(this);
    var $target = (typeof $this.data('toggler-target') != 'undefined') ? kakeibo.$body.find($this.data('toggler-target')) : $this;

    // Toggle class from data attribute
    $target.toggleClass($this.data('toggler-class'));

    e.stopPropagation();
    e.preventDefault();
    return false;
  });

  // ====================================
  // EVENTS / RESIZE ====================
  // kakeibo.$window.on('resize', function(e) {
  // });


  // ====================================
  // UNLOAD (delay before hide "loading")
  setTimeout(function() {
    kakeibo.unload();
  }, 400);
})();


// kakeibo.js
module.exports = kakeibo;
