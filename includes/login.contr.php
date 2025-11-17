<?php
declare(strict_types=1);

// Check for empty input
function is_input_empty(string $email, string $password): bool 
{
    return empty($email) || empty($password);
}

// Check if employee exists 
function is_employeeEmail_wrong(?array $result): bool 
{
    return $result === null;
}

// Check if password is correct
function is_password_wrong(string $password, string $hashedPwd): bool 
{
    return !password_verify($password, $hashedPwd);
}