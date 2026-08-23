<?php

namespace WaTemplates\Contracts;

/**
 * Uploads header media and returns a `header_handle`.
 *
 * Note this is not the media id `POST /<PHONE_NUMBER_ID>/media` returns — that
 * id is for sending, the handle is for template creation, and the two are not
 * interchangeable. The handle comes from the app-scoped Resumable Upload API,
 * which is why the implementation belongs to the host and not to this package.
 *
 * Null disables media headers, media carousels and limited-time offers.
 */
interface MediaUploader
{
    public function uploadForTemplate(string $bytes, string $mime, string $fileName): string;

    /**
     * @return list<string>
     */
    public function supportedTypes(): array;
}
