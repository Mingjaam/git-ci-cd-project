<?php
session_start();

require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';

requireLogin();

function getAllUsers($mysqli) {
    $result = $mysqli->query('SELECT id, username, created_at FROM users ORDER BY id ASC');
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function findUserById($mysqli, $id) {
    $stmt = $mysqli->prepare('SELECT id, username FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function isDuplicate($mysqli, $username, $exceptId = 0) {
    $stmt = $mysqli->prepare('SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1');
    $stmt->bind_param('si', $username, $exceptId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() !== null;
}

$flash   = ['type' => '', 'msg' => ''];
$editId  = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editRow = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_user') {
        $uid     = (int)($_POST['user_id'] ?? 0);
        $newName = trim($_POST['username'] ?? '');
        $newPw   = $_POST['password'] ?? '';

        if ($uid <= 0 || strlen($newName) < 3 || strlen($newName) > 50) {
            $flash  = ['type' => 'err', 'msg' => '아이디는 3~50자로 입력해 주세요.'];
            $editId = $uid;
        } elseif (isDuplicate($mysqli, $newName, $uid)) {
            $flash  = ['type' => 'err', 'msg' => '이미 사용 중인 아이디입니다.'];
            $editId = $uid;
        } elseif ($newPw !== '' && strlen($newPw) < 4) {
            $flash  = ['type' => 'err', 'msg' => '비밀번호는 4자 이상이어야 합니다.'];
            $editId = $uid;
        } else {
            try {
                if ($newPw === '') {
                    $stmt = $mysqli->prepare('UPDATE users SET username = ? WHERE id = ?');
                    $stmt->bind_param('si', $newName, $uid);
                } else {
                    $hash = password_hash($newPw, PASSWORD_BCRYPT);
                    $stmt = $mysqli->prepare('UPDATE users SET username = ?, password_hash = ? WHERE id = ?');
                    $stmt->bind_param('ssi', $newName, $hash, $uid);
                }
                $stmt->execute();
                if ($uid === (int)$_SESSION['uid']) {
                    $_SESSION['uname'] = $newName;
                }
                $flash  = ['type' => 'ok', 'msg' => '회원 정보를 수정했습니다.'];
                $editId = 0;
            } catch (mysqli_sql_exception $e) {
                $flash  = ['type' => 'err', 'msg' => $e->getCode() === 1062 ? '이미 사용 중인 아이디입니다.' : '수정 중 오류가 발생했습니다.'];
                $editId = $uid;
            }
        }
    }

    if ($action === 'delete_user') {
        $uid = (int)($_POST['user_id'] ?? 0);
        if ($uid <= 0) {
            $flash = ['type' => 'err', 'msg' => '잘못된 요청입니다.'];
        } elseif ($uid === (int)$_SESSION['uid']) {
            $flash = ['type' => 'err', 'msg' => '본인 계정은 삭제할 수 없습니다.'];
        } else {
            $stmt = $mysqli->prepare('DELETE FROM users WHERE id = ? LIMIT 1');
            $stmt->bind_param('i', $uid);
            $stmt->execute();
            $flash = $stmt->affected_rows === 1
                ? ['type' => 'ok',  'msg' => '회원을 삭제했습니다.']
                : ['type' => 'err', 'msg' => '해당 회원을 찾을 수 없습니다.'];
        }
    }
}

$users = getAllUsers($mysqli);
if ($editId > 0) {
    $editRow = findUserById($mysqli, $editId);
}
$mysqli->close();

$pageTitle = $editRow ? '회원 정보 수정' : '회원 관리';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> | 회원시스템</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body class="admin-body">
  <div class="admin-wrap">

    <div class="admin-header">
      <div>
        <h1><?= htmlspecialchars($pageTitle) ?></h1>
        <p class="user-info">접속 중: <?= htmlspecialchars($_SESSION['uname']) ?></p>
      </div>
      <a href="logout.php" class="logout-link">로그아웃</a>
    </div>

    <?php if ($flash['msg'] !== '') : ?>
      <div class="<?= $flash['type'] === 'ok' ? 'flash-ok' : 'flash-err' ?>">
        <?= htmlspecialchars($flash['msg']) ?>
      </div>
    <?php endif; ?>

    <div class="content-card">
      <?php if ($editRow) : ?>
        <h2>회원 정보 수정</h2>
        <form method="post" action="admin_users.php">
          <input type="hidden" name="action" value="update_user">
          <input type="hidden" name="user_id" value="<?= (int)$editRow['id'] ?>">
          <div class="field-group">
            <label for="e-id">아이디</label>
            <input type="text" name="username" id="e-id" class="text-input"
              value="<?= htmlspecialchars($editRow['username']) ?>"
              minlength="3" maxlength="50" autocomplete="username" required>
          </div>
          <div class="field-group">
            <label for="e-pw">새 비밀번호 <span style="font-size:0.78rem;color:#94a3b8">(변경 시에만 입력)</span></label>
            <input type="password" name="password" id="e-pw" class="text-input"
              placeholder="그대로 두면 변경 안 됨" autocomplete="new-password">
          </div>
          <button type="submit" class="submit-btn">저장</button>
        </form>
        <a href="admin_users.php" class="back-link">← 목록으로 돌아가기</a>

      <?php else : ?>
        <h2>전체 회원 목록</h2>
        <table>
          <thead>
            <tr>
              <th>번호</th>
              <th>아이디</th>
              <th>가입일시</th>
              <th style="text-align:right">관리</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($users) === 0) : ?>
              <tr><td colspan="4" class="no-data">등록된 회원이 없습니다.</td></tr>
            <?php endif; ?>
            <?php foreach ($users as $u) : ?>
              <tr>
                <td><?= (int)$u['id'] ?></td>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><?= htmlspecialchars($u['created_at']) ?></td>
                <td class="td-actions">
                  <a href="admin_users.php?edit=<?= (int)$u['id'] ?>" class="edit-link">수정</a>
                  <?php if ((int)$u['id'] === (int)$_SESSION['uid']) : ?>
                    <span class="disabled-text">삭제 불가</span>
                  <?php else : ?>
                    <form method="post" action="admin_users.php" style="display:inline"
                      onsubmit="return confirm('정말 삭제할까요? 되돌릴 수 없습니다.')">
                      <input type="hidden" name="action" value="delete_user">
                      <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                      <button type="submit" class="del-btn">삭제</button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

  </div>
</body>
</html>
