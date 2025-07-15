<?php

namespace App\Controller;

use App\Util\ValidationErrorHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationListInterface;

abstract class AbstractApiController extends AbstractController
{
    /**
     * Returns a JSON response with validation errors
     *
     * @param array $errors
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function validationError(array $errors, int $statusCode = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return ValidationErrorHandler::createValidationErrorResponse($errors, $statusCode);
    }

    /**
     * Returns a JSON response with form validation errors
     *
     * @param FormInterface $form
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function formValidationError(FormInterface $form, int $statusCode = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return ValidationErrorHandler::createFormErrorResponse($form, $statusCode);
    }

    /**
     * Returns a JSON response with constraint violation errors
     *
     * @param ConstraintViolationListInterface $violations
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function constraintViolationError(ConstraintViolationListInterface $violations, int $statusCode = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return ValidationErrorHandler::createConstraintViolationResponse($violations, $statusCode);
    }

    /**
     * Returns a successful JSON response
     *
     * @param mixed $data
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function success($data = null, int $statusCode = Response::HTTP_OK): JsonResponse
    {
        return $this->json([
            'status' => 'success',
            'data' => $data
        ], $statusCode);
    }

    /**
     * Returns an error JSON response
     *
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function error(string $message, int $statusCode = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return $this->json([
            'status' => 'error',
            'message' => $message
        ], $statusCode);
    }
}
