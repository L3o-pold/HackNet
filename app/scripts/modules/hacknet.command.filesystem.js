angular.module('hackNet.command.filesystem', ['hackNet.command.tools'])

    .provider('fileSystemConfiguration', function () {
        var provider = function () {
            var me = {};
            me.directorySeparator = "\\";
            me.$get = [function () {
                return me;
            }];
            return me;
        };

        return provider();
    })

    .service('storage', ['$resource',
        function ($resource) {
            var me = {};

            var File = $resource('http://www.hacknet.com/api/file/:fileId', {fileId: '@id'}, {
                query: {
                    method: 'GET',
                    params: {fileId: '@id'},
                    isArray: true
                }
            });

            me.getResource = function() {
                return File;
            };

            me.getItem = function (keyName) {
                var item = File.get({fileId: keyName}, function () {
                    if (keyName) {
                        return item.data.fileContent;
                    } else {
                        return item.data;
                    }
                });

                return item;
            };

            me.setItem = function (keyName, value) {
                var item = me.getItem(keyName);

                item = item ? item : new File(keyName);

                item.fileName = keyName;
                item.fileContent = value;

                item.$save();
            };

            me.removeItem = function (keyName) {
                return File.delete({fileId: keyName});
            };

            return me;
        }])

    .service('pathTools', ['fileSystemConfiguration',
        function (config) {
            var pathTools = function () {
                var me = {};
                me.isAbsolute = function (path) {
                    if (!path || path.length < config.directorySeparator.length)
                        return false;
                    return path.substring(0, config.directorySeparator.length) == config.directorySeparator;
                };

                me.addDirectorySeparator = function (path) {
                    if (path.substr(path.length - config.directorySeparator.length, config.directorySeparator.length) !== config.directorySeparator) {
                        path += config.directorySeparator;
                    }
                    return path;
                };

                me.addRootDirectorySeparator = function (path) {
                    if (!me.isAbsolute(path))
                        return config.directorySeparator + path;
                    return path;
                };

                me.combine = function () {
                    var result = '';
                    for (var i = 0; i < arguments.length; i++) {

                        var arg = arguments[i];

                        if (i != 0 && me.isAbsolute(arg))
                            throw new Error("When combining a path, only the first element can an absolute path.")
                        else if (i == 0)
                            arg = me.addRootDirectorySeparator(arg);
                        if (i != arguments.length - 1)
                            arg = me.addDirectorySeparator(arg);

                        result += arg;
                    }

                    return result;
                };

                me.directoryUp = function (path) {
                    if (path == config.directorySeparator)
                        return path;
                    var parts = path.split(config.directorySeparator);
                    var count = 1;
                    if (parts[parts.length - 1] == "")
                        count = 2;

                    for (var i = 0; i < count; i++) {
                        parts.pop();
                    }

                    if (parts[0] == "")
                        parts = parts.slice(1);
                    if (!parts.length)
                        return config.directorySeparator;

                    return me.combine.apply(me, parts);
                };

                me.isFileOfPath = function (basePath, path) {
                    if (path.substr(0, basePath.length) == basePath) {
                        var sp = path.substr(basePath.length);
                        if (me.isAbsolute(sp) && sp.indexOf(config.directorySeparator) === sp.lastIndexOf(config.directorySeparator)) {
                            sp = sp.substr(config.directorySeparator.length);
                            return sp != "_dir";
                        }
                        else {
                            return sp.indexOf(config.directorySeparator) == -1 && sp != "_dir";
                        }
                    }

                    return false
                };

                me.isDirectoryOfPath = function (basePath, path) {
                    if (path.substr(0, basePath.length) == basePath) {
                        var sp = path.substr(basePath.length);
                        if (sp.length > 5) {
                            var sp2 = sp.substr(0, sp.length - 5);
                            if (sp2 + "\\_dir" === sp) {
                                var pos = sp2.indexOf("\\");
                                return !!sp && (pos == -1 || pos == 0);
                            }
                        }
                    }
                    return false
                };

                me.getPathItemName = function (path) {
                    var parts = path.split(config.directorySeparator);
                    var last = parts[parts.length - 1];
                    if (last == "_dir") {
                        if (parts.length >= 3)
                            return parts[parts.length - 2];
                        else
                            return config.directorySeparator;
                    }
                    else if (last == "")
                        return config.directorySeparator;
                    else
                        return last;
                };

                var fileNameValidator = /^[\w_.\-]+$/;
                me.isFileNameValid = function (name) {
                    return !!name && name[0] != "_" && !!name.match(fileNameValidator);
                };

                var dirNameValidator = /^[\w_\-]+$/;
                me.isDirNameValid = function (name) {
                    return !!name && name[0] != "_" && !!name.match(dirNameValidator);
                };

                return me;
            };
            return pathTools();
        }])

    .service('fileSystem', ['fileSystemConfiguration',
        'pathTools',
        'storage',
        '$resource',
        function (config, pathTools, storage, $resource) {
            var fs = function () {
                var me = {};
                var _currentPath = config.directorySeparator;

                if (!storage.getItem(config.directorySeparator + "_dir"))
                    storage.setItem(config.directorySeparator + "_dir", "_dir");

                me.path = function (path) {

                    if (path == "..") {
                        _currentPath = pathTools.directoryUp(_currentPath);
                    }
                    else if (path && !pathTools.isDirNameValid(path))
                        throw new Error("The directory name is not valid");
                    else if (path) {

                        var dirkey = pathTools.combine(_currentPath, path, "_dir");

                        var fileResource = storage.getResource();
                        var item = fileResource.get({fileId: dirkey});

                        var content = item.$promise.then(function () {
                            _currentPath = pathTools.combine(_currentPath, path);
                            return _currentPath;
                        }, function() {
                            return null;
                        });

                        return content;
                    }

                    return _currentPath;
                };

                me.list = function () {
                    var result = {
                        directories: [],
                        files: []
                    };

                    if (_currentPath != config.directorySeparator)
                        result.directories.push("..");

                    console.log('List files from ' + _currentPath);

                    /**
                     * That's bad...
                     */
                    var fileResource = storage.getResource();
                    var item = fileResource.get({});

                    var content = item.$promise.then(function (files) {
                        for (var i in files.data) {

                            var fileName = files.data[i].fileName;

                            if (pathTools.isFileOfPath(_currentPath, fileName)) {
                                result.files.push(pathTools.getPathItemName(fileName));
                            }
                            else if (pathTools.isDirectoryOfPath(_currentPath, fileName)) {
                                result.directories.push(pathTools.getPathItemName(fileName));
                            }
                        }
                        result.directories.sort();
                        result.files.sort();

                        return result;
                    }, function() {
                        return null;
                    });

                    return content;
                };

                me.existsDir = function (path, failIfNotExist) {

                    if (!pathTools.isDirNameValid(path))
                        throw new Error("The directory name is not valid");

                    var dirkey = pathTools.combine(_currentPath, path, "_dir");
                    var exists = storage.getItem(dirkey);
                    return exists;
                };

                me.createDir = function (path) {

                    if (!pathTools.isDirNameValid(path))
                        throw new Error("The directory name is not valid");

                    if (!pathTools.isDirNameValid(pathTools.getPathItemName(path)))
                        throw new Error("Invalid directory name");


                    me.existsDir(path).$promise.then(function() {
                        throw new Error("The directory already exists.");
                    }, function() {
                        var dirkey = pathTools.combine(_currentPath, path, "_dir");
                        storage.setItem(dirkey, "_dir");
                    });
                };

                me.removeDir = function (path) {

                    console.log("Remove dir: " + path + " on: " + _currentPath);

                    if (!pathTools.isDirNameValid(path))
                        throw new Error("The directory name is not valid");

                    var folder = me.existsDir(path, true);

                    folder.$promise.then(function() {

                        var dirkey = pathTools.combine(_currentPath, path, "_dir");
                        path = pathTools.combine(_currentPath, path);
                        console.log("Full path: " + path);
                        var keys = [];

                        /**
                         * That's bad...
                         */
                        var fileResource = storage.getResource();
                        var item = fileResource.get({});

                        item.$promise.then(function (files) {

                            storage.removeItem(dirkey);

                            for (var i in files.data) {

                                var fileName = files.data[i].fileName;

                                if (pathTools.isFileOfPath(path, fileName)) {
                                    storage.removeItem(fileName);
                                }
                            }
                        }, function() {
                            throw new Error("Error during delete the folder");
                        });

                        return true;
                    }, function() {
                        return false;
                    });

                    return folder;
                };

                me.writeFile = function (name, content) {
                    if (!pathTools.isFileNameValid(name))
                        throw new Error("Invalid file name");
                    if (!content)
                        throw new Error("No content has been passed");

                    var filekey = pathTools.combine(_currentPath, name);
                    storage.setItem(filekey, content);
                };

                me.appendToFile = function (name, content) {
                    if (!pathTools.isFileNameValid(name))
                        throw new Error("Invalid file name");
                    if (!content)
                        throw new Error("No content has been passed");

                    var filekey = pathTools.combine(_currentPath, name);
                    var prevcontent = storage.getItem(filekey);
                    storage.setItem(filekey, (prevcontent ? prevcontent + "\n" : "") + content);
                };

                me.deleteFile = function (name) {
                    console.log("Remove file: " + name + " on: " + _currentPath);
                    if (!pathTools.isFileNameValid(name))
                        throw new Error("Invalid file name");
                    var filekey = pathTools.combine(_currentPath, name);

                    var fileResource = storage.getResource();
                    var item = fileResource.get({fileId: filekey});

                    var promise = item.$promise.then(function () {
                        storage.removeItem(filekey);
                        return true;
                    }, function() {
                        return false;
                    });

                    return promise;
                };

                me.readFile = function (name) {
                    if (!pathTools.isFileNameValid(name))
                        throw new Error("Invalid file name");

                    var filekey = pathTools.combine(_currentPath, name);

                    var fileResource = storage.getResource();

                    var item = fileResource.get({fileId: filekey});

                    var promise = item.$promise.then(function () {
                        return item.data.fileContent;
                    }, function() {
                        return null;
                    });

                    return promise;
                };

                return me;
            };
            return fs();
        }])

    .config(['commandBrokerProvider',
        function (commandBrokerProvider) {

            var pwdCommand = function () {
                var me = {};
                var fs = null;
                me.command = 'pwd';
                me.description = ['Shows current directory.'];
                me.init = ['fileSystem',
                    function (fileSystem) {
                        fs = fileSystem;
                    }];
                me.handle = function (session) {
                    session.output.push({
                        output: true,
                        text: [fs.path()],
                        breakLine: true
                    });
                };
                return me;
            };
            commandBrokerProvider.appendCommandHandler(pwdCommand());

            var cdCommand = function () {
                var me = {};
                var fs = null;
                me.command = 'cd';
                me.description = ['Changes directory.',
                    "Syntax: cd <path>",
                    "Example: cd myDirectory",
                    "Example: cd .."];
                me.init = ['fileSystem',
                    function (fileSystem) {
                        fs = fileSystem;
                    }];
                me.handle = function (session, path) {
                    if (!path)
                        throw new Error("A directory name is required");

                    /**
                     * @todo remove duplicate code
                     * Get that from config
                     */
                    if (path == "..") {
                        session.commands.push({
                            command: 'change-prompt',
                            prompt: {
                                path: fs.path(path)
                            }
                        });
                    } else {
                        fs.path(path).then(
                            function(content) {
                                if (content == null) {
                                    throw new Error("The directory '" + path + "' does not exist.");
                                }

                                session.commands.push({
                                    command: 'change-prompt',
                                    prompt: {
                                        path: content
                                    }
                                });
                            }
                        );
                    }

                };
                return me;
            };
            commandBrokerProvider.appendCommandHandler(cdCommand());

            var mkdirCommand = function () {
                var me = {};
                var fs = null;
                me.command = 'mkdir';
                me.description = ['Creates directory.',
                    "Syntax: mkdir <directoryName>",
                    "Example: mkdir myDirectory"];
                me.init = ['fileSystem',
                    function (fileSystem) {
                        fs = fileSystem;
                    }];
                me.handle = function (session, path) {
                    if (!path)
                        throw new Error("A directory name is required");
                    fs.createDir(path);
                    session.output.push({
                        output: true,
                        text: ["Directory created."],
                        breakLine: true
                    });
                };
                return me;
            };
            commandBrokerProvider.appendCommandHandler(mkdirCommand());

            var rmdirCommand = function () {
                var me = {};
                var fs = null;
                me.command = 'rmdir';
                me.description = ['Removes directory.',
                    "Syntax: rmdir <directoryName>",
                    "Example: rmdir myDirectory"];
                me.init = ['fileSystem',
                    function (fileSystem) {
                        fs = fileSystem;
                    }];
                me.handle = function (session, path) {
                    if (!path)
                        throw new Error("A directory name is required");

                    fs.removeDir(path).$promise.then(
                        function(content) {
                            if (content == null) {
                                throw new Error("The directory '" + path + "' does not exist.");
                            }

                            session.output.push({
                                output: true,
                                text: ["Directory removed."],
                                breakLine: true
                            });
                        }
                    );
                };
                return me;
            };
            commandBrokerProvider.appendCommandHandler(rmdirCommand());

            var lsCommand = function () {
                var me = {};
                var fs = null;
                var l = null;

                me.command = 'ls';
                me.description = ['List directory contents'];
                me.init = ['fileSystem',
                    function (fileSystem) {
                        fs = fileSystem;
                    }];
                me.handle = function (session) {
                    var output = [];

                    fs.list().then(function(files) {
                        for (var i = 0; i < files.directories.length; i++) {
                            output.push("[DIR]\t\t" + files.directories[i]);
                        }
                        for (var i = 0; i < files.files.length; i++) {
                            output.push("     \t\t" + files.files[i]);
                        }
                        output.push("");
                        output.push("Total: " + (files.directories.length + files.files.length));

                        session.output.push({
                            output: true,
                            text: output,
                            breakLine: true
                        });
                    });
                };
                return me;
            };
            commandBrokerProvider.appendCommandHandler(lsCommand());

            var catCommand = function () {
                var me = {};
                var fs = null;
                var content = null;

                me.command = 'cat';
                me.description = ['Reads file.',
                    "Syntax: cat <fileName>",
                    "Example: cat file.txt"];
                me.init = ['fileSystem',
                    function (fileSystem) {
                        fs = fileSystem;
                    }];
                me.handle = function (session, path) {
                    if (!path)
                        throw new Error("A file name is required");

                    fs.readFile(path).then(function(content) {
                        if (content == null) {
                            session.output.push({
                                output: true,
                                text: "The file does not exist".split('\n'),
                                breakLine: true
                            });
                        }

                        var outtext = content ? content.split('\n') : [];
                        session.output.push({
                            output: true,
                            text: outtext,
                            breakLine: true
                        });
                    });
                };
                return me;
            };
            commandBrokerProvider.appendCommandHandler(catCommand());

            var rmCommand = function () {
                var me = {};
                var fs = null;
                me.command = 'rm';
                me.description = ['Removes file.',
                    "Syntax: rm <fileName>",
                    "Example: rm file.txt"];
                me.init = ['fileSystem',
                    function (fileSystem) {
                        fs = fileSystem;
                    }];
                me.handle = function (session, path) {
                    if (!path)
                        throw new Error("A file name is required");

                    fs.deleteFile(path).then(function(content) {
                        var message = content ? "File deleted." : "The file does not exist".split('\n');

                        session.output.push({
                            output: true,
                            text: [message],
                            breakLine: true
                        });
                    });
                };
                return me;
            };
            commandBrokerProvider.appendCommandHandler(rmCommand());

            var createFileRedirection = function () {
                var me = {};
                var fs = null;
                me.command = '>';
                me.init = ['fileSystem',
                    function (fileSystem) {
                        fs = fileSystem;
                    }];
                me.handle = function (session, path) {
                    if (!path)
                        throw new Error("A file name is required");

                    if (session.input) {
                        var content = '';
                        for (var i = 0; i < session.input.length; i++) {
                            for (var j = 0; j < session.input[i].text.length; j++) {
                                content += session.input[i].text[j];
                                if (j != session.input[i].text.length - 1)
                                    content += '\n';
                            }
                        }
                        fs.writeFile(path, content);
                    }
                };
                return me;
            };
            commandBrokerProvider.appendRedirectorHandler(createFileRedirection());

            var appendFileRedirection = function () {
                var me = {};
                var fs = null;
                me.command = '>>';
                me.init = ['fileSystem',
                    function (fileSystem) {
                        fs = fileSystem;
                    }];
                me.handle = function (session, path) {
                    if (!path)
                        throw new Error("A file name is required");

                    if (session.input) {
                        var content = '';
                        for (var i = 0; i < session.input.length; i++) {
                            for (var j = 0; j < session.input[i].text.length; j++) {
                                content += session.input[i].text[j];
                                if (j != session.input[i].text.length - 1)
                                    content += '\n';
                            }
                        }
                        fs.appendToFile(path, content);
                    }
                };
                return me;
            };
            commandBrokerProvider.appendRedirectorHandler(appendFileRedirection());
        }])

    .run(['fileSystemConfiguration',
        'storage',
        function (fs, storage) {
            if (!storage.getItem(fs.directorySeparator + "_dir"))
                storage.setItem(fs.directorySeparator + "_dir", "_dir");
        }])

;
