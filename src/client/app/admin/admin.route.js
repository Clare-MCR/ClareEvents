(function () {
  'use strict';

  angular
    .module('app.admin')
    .run(appRun);

  appRun.$inject = ['routerHelper'];
  /* @ngInject */
  function appRun(routerHelper) {
    routerHelper.configureStates(getStates());
  }

  function getStates() {
    return [
      {
        state: 'admin',
        config: {
          url: '/admin',
          templateUrl: 'app/admin/admin.html',
          controller: 'AdminController',
          controllerAs: 'vm',
          title: 'Admin',
          resolve: {
            /* @ngInject */
            userPrepService: userPrepService
          },
          settings: {
            nav: 3,
            content: '<i class="fa fa-lock"></i> Admin',
            admin: true
          }
        }
      }
    ];
  }

  /* @ngInject */
  function userPrepService(UserServices) {
    return UserServices.get().$promise;
  }
})();