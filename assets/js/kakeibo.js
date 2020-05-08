require('../css/kakeibo.scss');

// Add ChartJS
var ChartJS = require('chart.js');

// NOTE: Useful for dark theme
// ChartJS.defaults.global.defaultFontColor = 'rgba(255, 255, 255, .75)';

var kakeibo = {
  _locale: 'fr',
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
    $lists: null,
    init: function() {
      var self = this;
      this.$panel = kakeibo.$body.find('.app-panel--transaction-form');
      this.$lists = kakeibo.$body.find('.list-transactions');

      kakeibo.$body.on('click', '.toggle-panel-transaction', function() {
        var $btn = $(this);
        self.toggle_panel($btn.data('panel-type'), $btn.data('panel-id-edit'));
      });
    },
    // TODO get this using a config
    url_load: '/transactions/get/',
    url_delete: '/transactions/delete/',
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
    delete: function() {
      console.log('[transaction:delete]');
      // $.ajax({
      //   url     : this.url_delete + id_trans,
      //   success : function(response) {
      //     callback(response);
      //   },
      //   error   : function(response) {
      //     console.warn(response);
      //   }
      // });
    },
    create_item_date_row: function(date) {
      var date_obj = new Date(date);
      return '<div class="-item -item-date" data-kb-date-formatted="' + date + '">' +
        date_obj.toLocaleDateString(kakeibo._locale,
          { weekday: undefined, year: 'numeric', month: 'long', day: 'numeric' }) +
      '</div>';
    },
    find_item_date_in_list: function($list, transaction) {
      var item_date_matched   = null;
      var last_date_matched   = null;
      var total_transactions  = 0;
      var transaction_date    = new Date(transaction.date);

      // Loop on item to find a matching date
      $list.find('.-item').each(function(index) {
        var $item = $(this);

        if ($item.hasClass('-item-date')) {
          var item_date = new Date($item.data('kb-date-formatted'));
          if ($item.data('kb-date-formatted') == transaction.date) {
            item_date_matched = { index: index, $node: $item };
            return false;
          } else if (transaction_date > item_date) {
            // IF transaction is superior to current item date
            //  > need to add new date at top of the list OR before current date
            var $new_item = $(kakeibo.transaction.create_item_date_row(transaction.date));
            // // Push new HTML date item before current one
            $item.before($new_item);

            item_date_matched = {
              index       : index,
              $node       : $new_item
            };

            return false;
          } else {
            last_date_matched = { index: index, $node: $item };
          }
        } else if ($item.hasClass('-item-transac')) {
          total_transactions++;
        }
      });

      return {
        matched_date  : item_date_matched,
        last_date     : last_date_matched,
        total_trans   : total_transactions
      };
    },
    manage: function(transaction, bank_account, is_edit) {
      // console.log('[transaction:manage]', transaction, bank_account, is_edit);
      // Data
      var currency = bank_account.currency_entity;
      var category = transaction.category_entity;
      var url_delete  = this.url_delete;
      var has_edit    = this.$panel.length > 0;
      var transaction_date  = new Date(transaction.date);

      // Add transaction to list
      if (is_edit == false) {
        this.$lists.each(function() {
          var $list = $(this);
          // List data attributes/config
          var limit       = typeof $list.data('kb-limit-items') != 'undefined' ? parseInt($list.data('kb-limit-items')) : null;
          var date_start  = typeof $list.data('kb-date-start') != 'undefined' ? new Date($list.data('kb-date-start')) : null ;
          var date_end    = typeof $list.data('kb-date-end') != 'undefined' ? new Date($list.data('kb-date-end')) : null ;
          var is_period_valid = (date_start === null || transaction_date >= date_start) &&
              (date_end === null || transaction_date <= date_end);

          // Add transaction ONLY IF date period is valid
          if (is_period_valid == true) {
            var date_find = kakeibo.transaction.find_item_date_in_list($list, transaction);

            // Need to push transaction at end of the list
            if (date_find.matched_date === null && (limit == null || date_find.total_trans < limit)) {
              // Create new HTML date item
              var $new_date_transac = $(kakeibo.transaction.create_item_date_row(transaction.date));
              // Append new date item (.-item-date) based on transaciton date
              $list.append($new_date_transac);
              // Push matched date (based on transaction date) to date_find
              date_find.matched_date = { index: $list.find('.-item').length, $node: $new_date_transac };
            }

            // Add new transaction
            if (date_find.matched_date !== null) {
              if (date_find.matched_date.index != -1) {
                var btn_edit = '';
                if (has_edit == true) {
                  btn_edit = '<button class="dropdown-item btn-edit-transac toggle-panel-transaction"' +
                    'type="button" data-panel-type="edit" data-panel-id-edit="' + transaction.id + '">' +
                    'Modifier <span class="ml-1 icon icon-edit"></span>' +
                  '</button>';
                }

                date_find.matched_date.$node.after($('<div class="-item -item-transac" data-kb-id-transaction="' + transaction.id + '">' +
                  '<div class="col col-auto col-icon">' +
                    '<span class="-transac-category avatar" title="' + category.label + '">' +
                      '<span class="avatar-text rounded-circle" style="background-color: ' + category.color + ';">' +
                        '<span class="icon icon-' + category.icon + '"></span>' +
                      '</span>' +
                    '</span>' +
                  '</div>' +
                  '<div class="col col-text">' +
                    '<span class="-transac-label">' + transaction.label + '</span>' +
                    '<div class="-transac-details -more-info small text-muted">' + ((transaction.details != null) ? transaction.details : '') + '</div>' +
                  '</div>' +
                  '<div class="col col-price">' +
                    '<span class="-transac-amount text-price text-' + (transaction.amount > 0 ? 'success' : (transaction.amount < 0) ? 'danger' : 'warning') + '">' +
                      kakeibo.format.price(transaction.amount, currency.slug) +
                    '</span>' +
                  '</div>' +
                  '<div class="col col-more">'+
                    '<span class="kb-more-dots align-middle" data-toggle="dropdown" aria-expanded="false">' +
                      '<span class="dot"></span>' +
                    '</span>' +
                    '<div class="dropdown-menu dropdown-menu-right text-right">' +
                      btn_edit +
                      '<a class="dropdown-item btn-delete-transac" href="' + url_delete + transaction.id + '">' +
                        'Supprimer <span class="ml-1 icon icon-trash"></span>' +
                      '</a>' +
                    '</div>' +
                  '</div>' +
                  '</div>'));
              }
            } else {
              // console.log('meh...', limit);
            }
          }
        });
      } else {
        // Edit transaction item
        var $item_to_edit = this.$lists.find('.-item-transac[data-kb-id-transaction="' + transaction.id + '"]');
        var $transac_cat  = $item_to_edit.find('.-transac-category');
        var $transac_amount = $item_to_edit.find('.-transac-amount');
        var amount_status   = (transaction.amount > 0) ? 'success' : ((transaction.amount < 0) ? 'danger' : 'warning');

        // // Update transaction data
        $transac_amount.html(kakeibo.format.price(transaction.amount, currency.slug))
          .removeClass('text-success text-warning text-danger')
          .addClass('text-' + amount_status);
        $item_to_edit.find('.-transac-label').html(transaction.label);
        $item_to_edit.find('.-transac-details').html(transaction.details);
        $item_to_edit.attr('data-kb-date-formatted', transaction.date);
        // // Update category
        $transac_cat.attr('title', category.label);
        $transac_cat.find('.avatar-text').css('background-color', category.color);
        $transac_cat.find('.icon').remove();
        $transac_cat.find('.avatar-text').append($('<span class="icon icon-' + category.icon + '"/>'));

        // // Change transaction position ?
        if (transaction.date != transaction.old.date) {
          this.$lists.each(function() {
            var $list = $(this);
            // List data attributes/config
            var limit       = typeof $list.data('kb-limit-items') != 'undefined' ? parseInt($list.data('kb-limit-items')) : null;
            var date_start  = typeof $list.data('kb-date-start') != 'undefined' ? new Date($list.data('kb-date-start')) : null ;
            var date_end    = typeof $list.data('kb-date-end') != 'undefined' ? new Date($list.data('kb-date-end')) : null ;
            var is_period_valid = (date_start === null || transaction_date >= date_start) &&
                (date_end === null || transaction_date <= date_end);

            var $item_to_move = $list.find('.-item-transac[data-kb-id-transaction="' + transaction.id + '"]');

            if ($item_to_move.length > 0) {
              if (is_period_valid) {
                var date_find = kakeibo.transaction.find_item_date_in_list($list, transaction);

                if (date_find.matched_date !== null) {
                  date_find.matched_date.$node.after($item_to_move);

                  // Remove old date ?
                  if ($list.find('.-item-transac[data-kb-date-formatted="' + transaction.old.date + '"]').length < 1)
                    $list.find('.-item-date[data-kb-date-formatted="' + transaction.old.date + '"]').remove();
                }
              } else {
                // Remove item out of period
                $item_to_move.remove();
              }
            }
          });
        }
      }
    },
    after_update: function(data, is_edit) {
      // console.log('[transaction.after_update]', data, is_edit);
      // console.log(this.$lists);

      var bank_account  = data.default_bank_account;
      var currency      = bank_account.currency_entity;
      var transaction   = data.entity;

      // Manage transaction in list & co
      this.manage(transaction, bank_account, is_edit);

      // Manage amounts (balance, totals expenses & incomes)
      this.update_balance(bank_account.balance, currency);
      this.update_exp_and_inc(transaction, currency);

      // Close panel
      this.toggle_panel('close');
    },
    update_balance : function(new_balance, currency) {
      // console.log('update_balance: ', new_balance, currency);
      var $text = kakeibo.$bank_account_balance.find('.text-price');

      // Update text price
      $text.html(kakeibo.format.price(new_balance, currency.slug));

      // Toggle text-[success|warning|danger] to change balance color
      $text.removeClass('text-success text-warning text-danger');
      if (new_balance < 0) $text.addClass('text-danger');
      else if (new_balance > 0) $text.addClass('text-success');
      else $text.addClass('text-warning');
    },
    update_exp_and_inc : function(transaction, currency) {
      // console.log('[update_exp_and_inc] ', transaction);
      var amount = transaction.amount;
      var $bank_total = (amount < 0) ? kakeibo.$bank_account_total_expenses : kakeibo.$bank_account_total_incomes;

      // old = transaction edit need to adjust totals expenses & incomes
      if (typeof transaction.old != 'undefined') {
        // If not changing from expense to income
        //  > simple adjustment
        if ((transaction.old.amount < 0 && amount < 0) ||
          (transaction.old.amount > 0 && amount > 0)) {
          // console.log('[update_exp_and_inc:simple adjustment] old:' + transaction.old.amount + ', new:' + amount);
          amount -= transaction.old.amount;
        } else {
          // Need to add add money to expenses if change to income and vice-versa
          var $bank_total_to_adjust = (transaction.old.amount < 0) ? kakeibo.$bank_account_total_expenses : kakeibo.$bank_account_total_incomes;
          var date_start_adjust     = typeof $bank_total_to_adjust.data('kb-date-start') != 'undefined' ? new Date($bank_total_to_adjust.data('kb-date-start')) : false;
          var date_end_adjust       = typeof $bank_total_to_adjust.data('kb-date-end') != 'undefined' ? new Date($bank_total_to_adjust.data('kb-date-end')) : false;
          var date_old              = new Date(transaction.old.date);

          var $text       = $bank_total_to_adjust.find('.text-price');
          var old_total   = parseFloat($text.html().replace(/ /g, '').replace(',', '.'));
          var adjusted_total = old_total + (transaction.old.amount * -1);

          // Update amount value ONLY IF transaction is in current displayed date in totals
          if ((date_old >= date_start_adjust || date_start_adjust == false) &&
            (date_old <= date_end_adjust || date_end_adjust == false))
              $text.html(kakeibo.format.price(adjusted_total, currency.slug));
        }
      }

      var date_start  = typeof $bank_total.data('kb-date-start') != 'undefined' ? new Date($bank_total.data('kb-date-start')) : false;
      var date_end    = typeof $bank_total.data('kb-date-end') != 'undefined' ? new Date($bank_total.data('kb-date-end')) : false;
      var date        = new Date(transaction.date);

      var $text       = $bank_total.find('.text-price');
      var old_total   = parseFloat($text.html().replace(/ /g, '').replace(',', '.'));
      var new_total   = old_total + amount;

      // Update amount value ONLY IF transaction is in current displayed date in totals
      if ((date >= date_start || date_start == false) && (date <= date_end || date_end == false))
          $text.html(kakeibo.format.price(new_total, currency.slug));
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
          // Clear <form>
          kakeibo.forms.clear('transaction');
        }
      } else {
        // console.warn('Must define a valid kakeibo.$transaction');
      }
    }
  },
  forms : {
    $items : null,
    init : function() {
      var self = this;
      this.$items = kakeibo.$body.find('form');

      // EVENT:AJAX SUBMIT
      this.$items.filter('.-use-ajax-submit').on('submit', function(e) {
        self.submit(this);

        e.stopPropagation();
        e.preventDefault();
        return false;
      });

      // INPUT, TEXTAREA & SELECT DEFAULT VALUES
      this.$items.find('[data-form-default-value]').each(function() {
        $(this).val($(this).attr('data-form-default-value'));
      });
    },
    // ajax submit
    submit : function(form) {
      var $form     = $(form);
      var form_name = $form.attr('name');
      var is_edit   = $form.find('#' + form_name + '_id').length > 0;

      $.ajax({
        method  : $form.attr('method'),
        url     : $form.attr('action'),
        data    : $form.serialize(),
        success : function(r) {
          // Call entity updater
          if (typeof kakeibo[form_name]['after_update'] != 'undefined')
            kakeibo[form_name]['after_update'](r, is_edit);
        },
        error   : function() { /* TODO */ }
      })
    },
    fill : function(name, data) {
      var $form = this.$items.filter('[name="' + name + '"]');

      // Force input hidden for id
      $form.append($('<input type="hidden" name="id" id="' + name + '_id" required="required">'));

      // Fill inputs
      for (const property in data) {
        $form.find('#'+ name +'_' + property).val(data[property]);
      }
    },
    clear : function(name) {
      var $form     = this.$items.filter('[name="' + name + '"]');
      var $texts    = $form.find('input, textarea');
      var $selects  = $form.find('select');

      // Reset classic <input|textarea>
      $texts.not('[type="hidden"], [data-form-clear="false"]').val('');
      // Reset <input|textarea> with a front wanted value
      $texts.filter('[data-form-default-value]').each(function() {
        $(this).val($(this).attr('data-form-default-value'));
      });

      // Reset <select>
      $selects.not('[data-form-clear="false"]').each(function() {
        var $select = $(this);
        var $options = $select.find('option');
        var select_val = $options.first().val();

        if (typeof $select.data('form-default-value') != 'undefined') {
          select_val = $select.data('form-default-value');
        } else if ($options.filter('[selected]').length > 0) {
          select_val = $options.filter('[selected]').val();
        }

        $select.val(select_val);
      });

      // Delete hidden input for id
      $form.find('#' + name + '_id').remove();

      // Clear inputs (TODO)
      // console.log('TODO full clear form !');
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
  format: {
    // TODO: set dynamic lang param (replace fr-FR)
    price: function(amount, currency_slug) {
      var num_format = new Intl.NumberFormat(this._locale, { style: 'currency', currency: currency_slug });
      return num_format.format(amount).replace(' ', ' '); // NOTE: .replace weird space with a real one
    }
  },
  // = ~ init/construct
  launch: function() {
    // ====================================
    // NODES ==============================
    this.$bank_account_balance = this.$body.find('.bank-account-balance');
    this.$bank_account_total_expenses = this.$body.find('.bank-account-total-expenses');
    this.$bank_account_total_incomes  = this.$body.find('.bank-account-total-incomes');

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

    // Bootstrap tooltips
    $('[data-toggle="tooltip"]').tooltip();

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
