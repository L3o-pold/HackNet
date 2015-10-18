'use strict';

angular.module('hackNet')
    .factory('File', ['$resource',
    function ($resource) {
        return $resource('http://www.hacknet.com/api/user/:userId', {}, {
            query: {
                method: 'GET',
                params: {phoneId: 'phones'},
                isArray: true
            }
        });
    }
]);