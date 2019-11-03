function getAjaxUrlFor(server) {
    var path;
    switch (server) {
        case 'localhost':
            path = "http://" + server + "/erja/service/";
            break;
        case 'erja':
            path = "https://erja.jaakkouusitalo.fi/";
            break;
    }
    return path + "functions.php";
}
var ajaxUrl = getAjaxUrlFor('localhost');

