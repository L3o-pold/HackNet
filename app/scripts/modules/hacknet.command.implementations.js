angular.module('hackNet.command.implementations', ['hackNet.command.tools'])

.config(['commandBrokerProvider', function (commandBrokerProvider) {

    commandBrokerProvider.appendCommandHandler({
        command: 'clear',
        description: ['Clears the screen.'],
        handle: function (session) {
            session.commands.push({ command: 'clear' });
        }
    });

    commandBrokerProvider.appendCommandHandler({
        command: 'echo',
        description: ['Echoes input.'],
        handle: function (session) {
            var a = Array.prototype.slice.call(arguments, 1);
            session.output.push({ output: true, text: [a.join(' ')], breakLine: true });
        }
    });

    commandBrokerProvider.appendCommandHandler({
        command: 'connect',
        description: ['Connect to a distant computer.',
                      'Syntax: connect <ip>',
                      'Example: connect 192.168.0.1'],
        handle: function (session, ip) {
            if (!ip) {
                throw new Error("The parameter 'ip' is required, type 'help connect' to get help.");
            }

            session.output.push({
                output: true,
                text: ["Openning connection to " + irl + " ...",
                       "Type 'exit' to exit."],
                breakLine: true
            });

            session.commands.push({ command: 'change-prompt', prompt: { path: 'connect[' + ip+']'} });
            session.contextName = "connect";
            session.context = function () {
                var me = {};
                /**
                 * @todo Use user model to get target info
                 */

                /**
                 * @todo Refresh netMap
                 */

                /**
                 * @todo Refresh display
                 */
                return me;
            }();
        }
    });

    // this must be the last
    var helpCommandHandler = function () {
        var me = {};
        
        me.command = 'help';
        me.description = ['Provides instructions about how to use this terminal'];
        me.handle = function (session, cmd) {
            var list = commandBrokerProvider.describe();
            var outText = [];
            if (cmd) {
                for (var i = 0; i < list.length; i++) {
                    if (list[i].command == cmd) {
                        var l = list[i];
                        outText.push("Command help for: " + cmd);
                        for (var j = 0; j < l.description.length; j++) {
                            outText.push(l.description[j]);
                        }
                        break;
                    }
                }
                if(!outText.length)
                    outText.push("There is no command help for: " + cmd);
            }
            else {
                outText.push("Available commands:");

                for (var i = 0; i < list.length; i++) {
                    var str = "  " + list[i].command + "\t\t";
                    outText.push(str);
                }
                outText.push("");
                outText.push("Enter 'help <command>' to get help for a particular command.");
            }
            session.output.push({ output: true, text: outText, breakLine: true });
        };
        return me;
    };
    commandBrokerProvider.appendCommandHandler(helpCommandHandler());
}])

;