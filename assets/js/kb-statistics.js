require('../css/kb-statistics.scss');

var kb_statistics = {
  $body: null,
  launch: function() {
    // console.log("[statistics] Let's count !");
    var self = this;

    // Nodes
    this.$body = $('body');
    this.$change_period = this.$body.find('.stats-change-period');

    // Events
    this.$change_period.on('click', '.btn', function() {
      window.location.href = '/statistiques/' + self.$change_period.find('#stats-from').val() +
        '/' + self.$change_period.find('#stats-to').val();
    });
  }
};

// On doc ready
(function() {
  // Rocket launcher !
  kb_statistics.launch();
})();
