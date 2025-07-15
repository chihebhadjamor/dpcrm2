<?php

namespace App\Controller;

use App\Util\ValidationErrorHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractWebController extends AbstractController
{
    /**
     * Handles form submission and validation
     *
     * @param Request $request
     * @param FormInterface $form
     * @param callable $onSuccess
     * @param string $successMessage
     * @param string $errorMessage
     * @param string $template
     * @param array $templateParams
     * @return Response
     */
    protected function handleForm(
        Request $request,
        FormInterface $form,
        callable $onSuccess,
        string $successMessage = 'Operation completed successfully',
        string $errorMessage = 'There were errors in your submission',
        string $template = null,
        array $templateParams = []
    ): Response {
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $response = $onSuccess($form->getData());

            if ($response instanceof Response) {
                return $response;
            }

            $this->addFlash('success', $successMessage);

            if ($response !== null && is_string($response)) {
                return $this->redirect($response);
            }

            return $this->redirectToRoute('dashboard');
        }

        if ($form->isSubmitted()) {
            $this->addFlash('error', $errorMessage);

            // Add form errors to flash messages for better visibility
            $errors = ValidationErrorHandler::getFormErrors($form);
            foreach ($errors as $field => $fieldErrors) {
                foreach ($fieldErrors as $error) {
                    $errorMsg = $field === 'global' ? $error : "$field: $error";
                    $this->addFlash('form_error', $errorMsg);
                }
            }
        }

        if ($template) {
            return $this->render($template, array_merge($templateParams, [
                'form' => $form->createView(),
            ]));
        }

        return $this->redirectToRoute('dashboard');
    }

    /**
     * Adds validation errors to flash messages
     *
     * @param FormInterface $form
     * @return void
     */
    protected function addFormErrorsToFlash(FormInterface $form): void
    {
        $errors = ValidationErrorHandler::getFormErrors($form);
        foreach ($errors as $field => $fieldErrors) {
            foreach ($fieldErrors as $error) {
                $errorMsg = $field === 'global' ? $error : "$field: $error";
                $this->addFlash('form_error', $errorMsg);
            }
        }
    }
}
