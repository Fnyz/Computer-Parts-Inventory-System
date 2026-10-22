<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function test_required_and_optional_fields(): void
    {
        $v = Validator::make(['name' => '  ', 'email' => ''], ['name' => 'required', 'email' => 'email']);
        $this->assertSame(['name' => 'Enter the name.'], $v->errors());
    }

    public function test_rules_report_the_first_problem_per_field(): void
    {
        $v = Validator::make(
            ['price' => '12.345', 'qty' => '2.5', 'role' => 'owner', 'sku' => 'bad sku', 'password' => 'short', 'note' => str_repeat('x', 11)],
            ['price' => 'required|money', 'qty' => 'int', 'role' => 'in:admin,staff', 'sku' => 'regex:/^[A-Z0-9-]+$/', 'password' => 'minlen:8', 'note' => 'max:10'],
            ['sku' => 'SKU'],
        );
        $errors = $v->errors();
        $this->assertSame('Price must be an amount like 1250 or 1,250.50.', $errors['price']);
        $this->assertSame('Qty must be a whole number.', $errors['qty']);
        $this->assertSame('Choose a valid role.', $errors['role']);
        $this->assertSame('SKU has an invalid format.', $errors['sku']);
        $this->assertSame('Password must be at least 8 characters.', $errors['password']);
        $this->assertSame('Note can be at most 10 characters.', $errors['note']);
    }

    public function test_min_checks_numbers(): void
    {
        $this->assertTrue(Validator::make(['qty' => '-1'], ['qty' => 'min:0'])->fails());
        $this->assertFalse(Validator::make(['qty' => '0'], ['qty' => 'min:0'])->fails());
    }
}
