(function () {
    'use strict';

    var app = angular.module('hackNet', [
        'ngResource',
        'ngRoute',
        'UserApp',
        'ui.bootstrap',
        'vtortola.ng-terminal',
        'ng-terminal-example.command.tools',
        'ng-terminal-example.command.implementations',
        'ng-terminal-example.command.filesystem'
    ]);

    app.run(function($rootScope, user, $http) {
        user.init({ appId: '56191c36bd950' });

        $rootScope.$on('user.login', function() {
            $http.defaults.headers.common['Authorization'] = 'Basic ' + btoa('56191c36bd950:' + user.token());
            $rootScope.user = user;
        });

        $rootScope.$on('user.logout', function() {
            $http.defaults.headers.common['Authorization'] = null;
            $rootScope.user = null;
        });
    });
})();
