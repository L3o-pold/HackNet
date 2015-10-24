'use strict';

var NetMapCtrl = angular.module('NetMapCtrl', []);

NetMapCtrl.controller('NetMapListCtrl', ['$scope', 'computer', '$rootScope', function($scope, computer, $rootScope) {
    $rootScope.computers = computer.query();
}]);