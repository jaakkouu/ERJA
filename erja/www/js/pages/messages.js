moment.locale("fi");

$(function() {

    var getGroups = getUsersGroups();

    getGroups.done(function(groups) {
        var groups = JSON.parse(groups);
        if (groups.length) {
            $("#hasGroups").fadeIn();
            listUsersGroups(groups);
        } else {
            $("#noGroups").fadeIn();
        }
    });

    $(document).on("click", 'div.groups_listing_inner div.group', function() {
        $(".groups_listing_inner .group").removeClass("selected");
        $(this).addClass("selected");
        var getMessages = getMessagesByGroup($(this).data("groupid"));
        getMessages.done(function(messages) {
            $("#messages").html("");
            var messages = JSON.parse(messages);
            if (messages.length) {
                setMessages(messages);
            } else {
                $("#messages").append(
                    $("<p />").text("Ryhmässä ei ole vielä viestejä")
                )
            }
        });
    });

});