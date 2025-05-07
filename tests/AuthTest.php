<?php
use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase
{
    protected $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO(
            "mysql:host=localhost;dbname=sportmatchy_test",
            "root",
            "",
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    public function testUserRegistration()
    {
        $username = 'newuser';
        $email = 'newuser@example.com';
        $password = 'password123';

        $stmt = $this->pdo->prepare("
            INSERT INTO users (username, email, password_hash, created_at)
            VALUES (?, ?, ?, NOW())
        ");

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $result = $stmt->execute([$username, $email, $passwordHash]);

        $this->assertTrue($result);

        // Verify user was created
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        $this->assertEquals($username, $user['username']);
        $this->assertEquals($email, $user['email']);
        $this->assertTrue(password_verify($password, $user['password_hash']));
    }

    public function testUserLogin()
    {
        $email = 'test1@example.com';
        $password = 'password';

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        $this->assertNotFalse($user);
        $this->assertTrue(password_verify($password, $user['password_hash']));
    }

    public function testInvalidLogin()
    {
        $email = 'test1@example.com';
        $password = 'wrongpassword';

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        $this->assertNotFalse($user);
        $this->assertFalse(password_verify($password, $user['password_hash']));
    }
} 