(function () {
  'use strict';

  angular
    .module('app.dashboard')
    .controller('DashboardController', DashboardController);

    DashboardController.$inject = [/*'bookingsPrepService',*/ 'eventsPrepService', 'logger', '$state'];
  /* @ngInject */
    function DashboardController(/*bookingsPrepService,*/eventsPrepService, logger, $state) {
    var vm = this;
    vm.title = 'Status Today';
    //vm.punts = puntsPrepService;
    //vm.bookings = bookingsPrepService;

    activate();

    function activate() {
      logger.info('Viewing ' + $state.current.title);
    }

    function getuser() {
        vm.user = eventsPrepService.get(
        function (data) {
          vm.admin = data.admin === '1';
        }
      );
    }
  }
})();
