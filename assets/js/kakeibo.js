require('../css/kakeibo.scss');

// Add ChartJS
var ChartJS = require('chart.js');

// NOTE: Useful for dark theme
// ChartJS.defaults.global.defaultFontColor = 'rgba(255, 255, 255, .75)';

var kakeibo = {
  // Viewport sizes
  viewport: {
    $sizes: null,
    last_call_size: null,
    init: function() {
      this.$sizes = kakeibo.$body.find('.viewport-sizes > *');
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
    init: function() {
      var self    = this;
      this.$items = kakeibo.$body.find('.chart-js');

      this.$items.each(function() {
        var $chart = $(this),
            canvas = $chart.find('canvas')
            data_name   = $chart.data('chartjs-data-name'),
            data_type   = $chart.data('chartjs-data-type'),
            chart_type  = $chart.data('chartjs-type'),
            chart_min   = $chart.data('chartjs-min'),
            chart_max   = $chart.data('chartjs-max'),
            grid_color  = $chart.data('chartjs-grid-color'),
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

          if (typeof grid_color != 'undefined') {
            if (grid_color == 'white' || grid_color == 'light') {
              opts.scales = {
                xAxes: [{
                  gridLines: {
                    zeroLineColor: 'rgba(255, 255, 255, .5)',
                    color: 'rgba(255, 255, 255, .1)',
                  },
                  ticks: {
                    fontColor: 'rgba(255, 255, 255, .75)'
                  }
                }],
                yAxes: [{
                  gridLines: {
                    zeroLineColor: 'rgba(255, 255, 255, .5)',
                    color: 'rgba(255, 255, 255, .1)',
                  },
                  ticks: {
                    fontColor: 'rgba(255, 255, 255, .75)'
                  }
                }]
              };
            }
          }

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
  transaction: {
    $panel: null,
    init: function() {
      var self = this;
      this.$panel = kakeibo.$body.find('.app-panel--transaction-form');

      kakeibo.$body.on('click', '.toggle-panel-transaction', function() {
        var $btn = $(this);
        self.toggle_panel($btn.data('panel-type'), $btn.data('panel-id-edit'));
      });
    },
    url_load: '/trans/get/',
    load: function(id_trans, callback) {
      $.ajax({
        url     : this.url_load + id_trans,
        success : function(response) {
          callback(response);
        },
        error   : function(response) {
          console.warn(response);
        }
      });
    },
    update_entity_nodes: function(data) {
      console.log('[transaction.update_entity_nodes]', data);
      this.toggle_panel('close');
    },
    // toggle_panel = add / edit transaction (toggle_panel())
    toggle_panel: function(type, id_edit) {
      var self = this;
      type = (typeof type == 'undefined') ? 'add' : type;

      if (self.$panel!= null && self.$panel.length > 0) {
        // Display/Hide panel
        kakeibo.$body.toggleClass('app-panel--transaction-form--show');
        self.$panel.removeClass('-is-edit');

        // Load transaction data into form on edit
        if (type == 'edit') {
          id_edit = parseInt(id_edit);

          if (id_edit > 0) {
            self.$panel.addClass('-is-loading')
              .addClass('-is-edit');

            // load transaction data & fill form with them
            self.load(id_edit, function(r) {
              kakeibo.forms.fill('transaction', r.entity);
              self.$panel.removeClass('-is-loading');
            });
          } else {
            console.warn('[transaction.toggle_panel] invalid ID transaction, something went wrong...');
          }
        } else if (type == 'close') {
          console.log('[transaction.toggle_panel] clear form ?');
          kakeibo.forms.clear('transaction');
        }
      } else {
        // console.warn('Must define a valid kakeibo.$transaction');
      }
    }
  },
  forms: {
    $items : null,
    init: function() {
      var self = this;
      this.$items = kakeibo.$body.find('form');

      this.$items.filter('.-use-ajax-submit').on('submit', function(e) {
        self.submit(this);

        e.stopPropagation();
        e.preventDefault();
        return false;
      });
    },
    // ajax submit
    submit: function(form) {
      var $form = $(form);
      $.ajax({
        method  : $form.attr('method'),
        url     : $form.attr('action'),
        data    : $form.serialize(),
        success : function(r) {
          // Call entity updater
          if (typeof kakeibo[$form.attr('name')]['update_entity_nodes'] != 'undefined')
            kakeibo[$form.attr('name')]['update_entity_nodes'](r);
        },
        error   : function() { /* TODO */ }
      })
    },
    fill: function(name, data) {
      var $form = this.$items.filter('[name="' + name + '"]');

      // Force input hidden for id
      $form.append($('<input type="hidden" name="id" id="' + name + '_id" required="required">'));

      // Fill inputs
      for (const property in data) {
        $form.find('#'+ name +'_' + property).val(data[property]);
      }
    },
    clear: function(name) {
      var $form = this.$items.filter('[name="' + name + '"]');

      // Delete hidden input for id
      $form.find('#' + name + '_id').remove();

      // Clear inputs (TODO)
      console.log('TODO full clear form !');
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
  // = ~ preload
  lock: function() {
    // console.log("Hi ! I'm kakeibo.js");
    // Nodes
    this.$body    = $('body');
    this.$window  = $(window);

    // Set loading
    this.load();
  },
  // = ~ init/construct
  launch: function() {
    // ====================================
    // PLUGINS ============================
    // Forms
    this.forms.init();

    // Viewport
    this.viewport.init();

    // Wallet = Transactions manager
    this.transaction.init();

    // Charts
    this.chartJS.init();


    // ====================================
    // EVENTS / TRANSACTIONS ==============
    kakeibo.$trans_form_import = this.forms.$items.filter('.form-trans-import');
    kakeibo.$trans_form_import.on('change', '.first-import-file', function() {
      kakeibo.$trans_form_import.submit();
    });
    // Close panel on click on loader
    kakeibo.$body.on('click', '.app-loader', function() {
      kakeibo.transaction.toggle_panel('close');
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
  }
};

// Lock / preload
kakeibo.lock();

// On doc ready
(function() {
  // Rocket launcher !
  kakeibo.launch();
})();

// kakeibo.js
// module.exports = kakeibo;
