<?php
/**
 * groups.php - Group maintenance
 *
 * PHP version 5
 *
 * @category  Messaging
 * @package   SMS-Text-Messager
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2013 Hardcover LLC
 * @license   http://hardcoverwebdesign.com/license  MIT License
 *.@license   http://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version   GIT: 2013-12-1 database B
 * @link      http://smstextmessager.com/
 * @link      http://hardcoverwebdesign.com/
 */
require 'z/system/configuration.php';
require $includesPath . '/authorization.php';
require $includesPath . '/common.php';
//
// Variables
//
$adminPassPost = inlinePost('adminPass');
$groupName = inlinePost('groupName');
$message = null;
if (isset($_POST['adminPass']) and ($_POST['adminPass'] == null or $_POST['adminPass'] == '')) {
    $message = 'Your password is required for all recipient maintenance.';
}
if (isset($_POST['groupName']) and ($_POST['groupName'] == null or $_POST['groupName'] == '')) {
    $message = 'No group name was input.';
}
//
// Test password authentication
//
$dbh = new PDO($db);
$stmt = $dbh->prepare('SELECT pass FROM usersRecipients WHERE user=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute(array($_SESSION['username']));
$row = $stmt->fetch();
$dbh = null;
if (strval(crypt($adminPassPost, $row['pass'])) === strval($row['pass'])) {
    //
    // Buttons, insert and delete
    //
    if (isset($_POST['insert']) and $message == null) {
        $dbh = new PDO($db);
        $stmt = $dbh->prepare('SELECT groupName FROM groups WHERE groupName=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute(array($groupName));
        $row = $stmt->fetch();
        $dbh = null;
        if (isset($row['groupName'])) {
            header('Location: ' . $uri . 'groups.php');
            exit;
        }
        $dbh = new PDO($db);
        $stmt = $dbh->prepare('INSERT INTO groups (groupName) VALUES (?)');
        $stmt->execute(array($groupName));
        $dbh = null;
        mailAttachments($_SESSION['username'], $emailTo, $emailFrom, array($includesPath . '/databases/sms.sqlite', 'z/system/configuration.php'));
    }
    if (isset($_POST['delete']) and $message == null) {
        $dbh = new PDO($db);
        $stmt = $dbh->prepare('SELECT idGroup, groupName FROM groups WHERE groupName=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute(array($groupName));
        $row = $stmt->fetch();
        if (isset($row['groupName'])) {
            $stmt = $dbh->prepare('DELETE FROM groups WHERE idGroup=?');
            $stmt->execute(array($row['idGroup']));
            $stmt = $dbh->prepare('DELETE FROM send WHERE idGroup=?');
            $stmt->execute(array($row['idGroup']));
        }
        $stmt = $dbh->query('VACUUM');
        $dbh = null;
        mailAttachments($_SESSION['username'], $emailTo, $emailFrom, array($includesPath . '/databases/sms.sqlite', 'z/system/configuration.php'));
    }
} elseif (isset($_POST['insert']) or isset($_POST['update']) or isset($_POST['delete'])) {
    $message = 'The password is invalid.';
}
//
// Button, edit
//
$groupNameEdit = isset($_POST['edit']) ? $groupName : false;
//
// HTML
//
require 'z/includes/header1.inc';
echo '  <title>Group maintenance</title>' . "\n";
echo '  <script type="text/javascript" src="z/focus.js"></script>' . "\n";
require 'z/includes/header2.inc';
require 'z/includes/body.inc';
?>

  <h4 class="m"><a class="m" href="message.php">&nbsp;Message&nbsp;</a><a class="s" href="groups.php">&nbsp;Groups&nbsp;</a><a class="m" href="users.php">&nbsp;Users&nbsp;</a><a class="m" href="recipients.php">&nbsp;Recipients&nbsp;</a><a class="m" href="carriers.php">&nbsp;Carriers&nbsp;</a></h4>
<?php echoIfMessage($message); ?>

  <h1><span class="r">Groups</span></h1>

<?php
$rowcount = null;
$dbh = new PDO($db);
$stmt = $dbh->prepare('SELECT groupName FROM groups ORDER BY groupName');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute();
foreach ($stmt as $row) {
    extract($row);
    $rowcount++;
    echo '  <form action="' . $uri . 'groups.php" method="post">' . "\n";
    echo "    <p><span class=\"rp\">$groupName - count: $rowcount<br />\n";
    echo '    <input name="groupName" type="hidden" value="' . html($groupName) . '" /><input type="submit" value="Edit" name="edit" class="button" /></span></p>' . "\n";
    echo "  </form>\n\n";
}
$dbh = null;
?>
  <h1>Group maintenance</h1>

  <p>Recipients must belong to a group in order to receive messages. Not even messages sent to, All, will be delivered unless the recipient belongs to a defined group. Your password is required for all group maintenance.</p>

  <form action="<?php echo $uri; ?>groups.php" method="post">
    <p>Password<br />
    <input id="adminPass" name="adminPass" type="password" required="required" /></p>

    <h1>Add or delete groups</h1>

    <p>Group names must be unique.</p>

    <p>Group name<br />
    <input name="groupName" type="text" autofocus="autofocus" required="required"<?php echoIfValue($groupNameEdit); ?> /></p>

    <p><input type="submit" value="Add" name="insert" class="left" /><input type="submit" value="Delete" name="delete" class="right" /></p>
  </form>
</body>
</html>
