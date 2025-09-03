<?php

namespace Backstage\Fields\Concerns;

use Backstage\Fields\Services\RichEditorDataService;

trait HandlesRichEditorData
{
    /**
     * Convert RichEditor string values to array format for Filament v4 compatibility
     */
    protected function convertRichEditorStringsToArrays(array $data): array
    {
        return RichEditorDataService::convertRichEditorStringsToArrays($data, $this->getRecord()->valueColumn);
    }

    /**
     * Process form data to ensure RichEditor fields are in the correct format
     */
    protected function processFormDataForRichEditor(array $data): array
    {
        return RichEditorDataService::processFormDataForRichEditor($data);
    }

    /**
     * Convert HTML string to Filament v4 RichEditor array format
     */
    protected function convertStringToRichEditorArray(?string $html): array
    {
        return RichEditorDataService::convertStringToRichEditorArray($html);
    }
}
