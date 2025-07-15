<?php

namespace App\Util;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ValidationErrorHandler
{
    /**
     * Formats form errors into a user-friendly array
     *
     * @param FormInterface $form
     * @return array
     */
    public static function getFormErrors(FormInterface $form): array
    {
        $errors = [];

        foreach ($form->getErrors() as $error) {
            $errors['global'][] = $error->getMessage();
        }

        foreach ($form->all() as $childForm) {
            if ($childForm instanceof FormInterface) {
                if ($childErrors = $childForm->getErrors()) {
                    foreach ($childErrors as $error) {
                        $errors[$childForm->getName()][] = $error->getMessage();
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Formats constraint violations into a user-friendly array
     *
     * @param ConstraintViolationListInterface $violations
     * @return array
     */
    public static function getConstraintViolations(ConstraintViolationListInterface $violations): array
    {
        $errors = [];

        foreach ($violations as $violation) {
            $propertyPath = $violation->getPropertyPath();
            $errors[$propertyPath][] = $violation->getMessage();
        }

        return $errors;
    }

    /**
     * Creates a JSON response with validation errors
     *
     * @param array $errors
     * @param int $statusCode
     * @return JsonResponse
     */
    public static function createValidationErrorResponse(array $errors, int $statusCode = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return new JsonResponse([
            'status' => 'error',
            'message' => 'Validation failed',
            'errors' => $errors
        ], $statusCode);
    }

    /**
     * Creates a JSON response with form validation errors
     *
     * @param FormInterface $form
     * @param int $statusCode
     * @return JsonResponse
     */
    public static function createFormErrorResponse(FormInterface $form, int $statusCode = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return self::createValidationErrorResponse(self::getFormErrors($form), $statusCode);
    }

    /**
     * Creates a JSON response with constraint violation errors
     *
     * @param ConstraintViolationListInterface $violations
     * @param int $statusCode
     * @return JsonResponse
     */
    public static function createConstraintViolationResponse(ConstraintViolationListInterface $violations, int $statusCode = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return self::createValidationErrorResponse(self::getConstraintViolations($violations), $statusCode);
    }
}
