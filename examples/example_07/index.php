<?php
/**
 * Build a simple HTML page with multiple providers, opening provider authentication in a pop-up.
 */

require 'path/to/vendor/autoload.php';
require 'config.php';

use Hybridauth\Hybridauth;

$hybridauth = new Hybridauth($config);
$adapters = $hybridauth->getConnectedAdapters();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Example 07</title>
</head>
<body>
    <h1>Sign in</h1>

    <ul>

<?php
    foreach ($hybridauth->getProviders() as $name) {
        if (!isset($adapters[$name])) {
            echo '
        <li>
            <a href="#" onclick="javascript:auth_popup(\''. $name .'\');">
                Sign in with '. $name .'
            </a>
        </li>';
        }
    }
?>

    </ul>

<?php if ($adapters) { ?>
    <h1>You are logged in with:</h1>
    <ul>
<?php
        foreach ($adapters as $name=>$adapter) {
            echo '
        <li>
            '. $adapter->getUserProfile()->displayName .' from
            <i>'. $name .'</i>
            (<a href="'. $config['callback'] .'?logout='. $name .'">Log Out</a>)
        </li>';
        }
?>
    </ul>
<?php } ?>

</body>
</html>




<a href="#" onclick="javascript:auth_popup('Google');">Sign in with Google</a>
<a href="#" onclick="javascript:auth_popup('Facebook');">Sign in with Facebook</a>


<script>
    function auth_popup( provider ){
        // replace 'path/to/hybridauth' with the real path to this script
        var authWindow = window.open('https://path/to/hybridauth/examples/example_07/callback.php?provider='+provider, 'authWindow', 'width=600,height=400,scrollbars=yes');
        return false;
    }
</script>