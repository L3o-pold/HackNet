'use strict';

var netMapServices = angular.module('netMapServices', ['ngResource']);

netMapServices.factory('computer', ['$resource',
    function ($resource) {
        return $resource('http://www.hacknet.com/api/connect/:userIp', {}, {
            query: {method: 'GET', params: {userIp: '@id'}}
        });
    }]
);