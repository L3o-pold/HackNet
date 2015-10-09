'use strict';

angular.module('hackNet')
    .controller('MainCtrl', function ($scope, $timeout, AccessToken) {
        $scope.$on('oauth:login', function(event, token) {
            $scope.accessToken = token.access_token;
        });

        $scope.$on('oauth:logout', function(event) {
            $scope.accessToken = null;
        });

        $scope.$on('oauth:authorized', function(event, token) {
            $scope.accessToken = token.access_token;
            $scope.show = 'logged-in';
        });
    });