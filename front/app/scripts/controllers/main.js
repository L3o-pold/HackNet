'use strict';

angular.module('hackNet')
    .controller('MainCtrl', [
        '$scope',
        'commandBroker',
        '$resource',
        '$rootScope',
        function ($scope, $http, $resourceProvider) {
            var User = $resourceProvider('http://www.hacknet.com/api/user/');
            var test = User.get();
        }
    ]);