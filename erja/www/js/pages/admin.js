var myGroups,
    allComments;
$(function() {

    //if account doesnt exist
    if (window.localStorage.getItem("account") === null) {
        logout();
    } else {
        authenticate();
        setTimeout(function(){
            $("a[data-page='groups']").trigger("click");
        }, 0);
    }

    $("a[data-page='groups']").on("click", function() {
        myGroups = getGroups();
        myGroups.done(function(data) {
            $("#page-groups").html("");
            window.localStorage.setItem('groups', data);
            listGroups(JSON.parse(data));
        });
    });
    
    $("a[data-page='all_comments']").on("click", function() {
        allComments = getComments();
        allComments.done(function(data) {
            $("#page-all_comments").html("");
            window.localStorage.setItem('comments', data);
            listComments(JSON.parse(data));
        });
    });

});

function createGroup(group_name, group_password) {
    authenticate();
    $.post(ajaxUrl, {
        function: "group",
        action: "createGroup",
        id: getStringFromLocalStorage('account', 'id'),
        group_name: group_name,
        group_password: group_password
    }, function(data) {
        closeModal('.modal_create_group');
        listGroups(getGroups());
    });
}

function destroyGroup() {
    authenticate();
    var groupName = getStringFromLocalStorage("selected_group", "title"),
        groupId = getStringFromLocalStorage("selected_group", "id");
    if (confirm("Haluatko varmasti poistaa " + groupName + " ryhmän?")) {
        $.post(ajaxUrl, {
            function: "group",
            action: "deleteGroup",
            group_id: groupId,
            user_id: getStringFromLocalStorage("account", "id")
        }, function(data) {
            if (data == "deleted") {
                closeModal(".modal");
                setTimeout(function() {
                    $('.group_row[data-groupid='+ groupId +']').fadeOut().delay(400).remove();
                }, 400);
            }
        });
    } 
}