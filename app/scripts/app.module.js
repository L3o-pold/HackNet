(function () {
    'use strict';

    var app = angular.module('hackNet', [
        'ngResource',
        'ngRoute',
        'UserApp',
        'ui.bootstrap',
        'vtortola.ng-terminal',
        'NetMapCtrl',
        'netMapServices',
        'hackNet.command.tools',
        'hackNet.command.implementations',
        'hackNet.command.filesystem'
    ]);

    app.run(function($rootScope, user, $http) {
        user.init({ appId: '56191c36bd950' });

        $rootScope.$on('user.login', function() {
            $http.defaults.headers.common['Authorization'] = 'Basic ' + btoa('56191c36bd950:' + user.token());
            user.current.ip = '127.0.0.1';
            $rootScope.user = user;
        });

        $rootScope.$on('user.logout', function() {
            $http.defaults.headers.common['Authorization'] = null;
            $rootScope.user = null;
        });
    });
})();
