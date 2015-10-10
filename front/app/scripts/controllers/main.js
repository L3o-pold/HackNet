'use strict';

angular.module('hackNet')
    .controller('MainCtrl', ['$scope',
        'commandBroker',
        '$rootScope', function ($scope, $rootScope) {
            $rootScope.$on('user.login', function() {
                $http.defaults.headers.common.Authorization = 'Basic ' + btoa(':' + user.token());
            });

            $rootScope.$on('user.logout', function() {
                $http.defaults.headers.common.Authorization = null;
            });
    }]);