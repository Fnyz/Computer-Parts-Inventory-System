<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;

abstract class Controller
{
    protected function view(string $template, array $data = [], ?string $layout = 'layouts/app'): Response
    {
        return Response::html(View::render($template, $data, $layout));
    }

    protected function redirect(string $to, ?string $success = null): Response
    {
        if ($success !== null) {
            Session::flash('success', $success);
        }

        return Response::redirect($to);
    }

    /** Sends the user back to the form with error messages and their input kept. */
    protected function backWithErrors(Request $request, string $to, array $errors, ?string $summary = null): Response
    {
        Session::flashErrors($errors, $request->body);
        Session::flash('error', $summary ?? (count($errors) === 1 ? (string) reset($errors) : 'Check the highlighted fields.'));

        return Response::redirect($to);
    }

    /** Validates the submitted form fields. */
    protected function validate(Request $request, array $rules, array $labels = []): Validator
    {
        return Validator::make($request->body, $rules, $labels);
    }

    protected function findOr404(?array $row): array
    {
        if ($row === null) {
            throw HttpException::notFound();
        }

        return $row;
    }
}
