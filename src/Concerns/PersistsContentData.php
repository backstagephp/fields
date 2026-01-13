<?php

namespace Backstage\Fields\Concerns;

use Backstage\Fields\Models\Field as ModelsField;
use Backstage\Models\Tag;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

trait PersistsContentData
{
    protected function handleTags(): void
    {
        $tags = collect($this->data['tags'] ?? [])
            ->filter(fn ($tag) => filled($tag))
            ->map(fn (string $tag) => $this->record->tags()->updateOrCreate([
                'name' => $tag,
                'slug' => Str::slug($tag),
            ]))
            ->each(fn (Tag $tag) => $tag->sites()->syncWithoutDetaching($this->record->site));

        $this->record->tags()->sync($tags->pluck('ulid')->toArray());
    }

    protected function handleValues(): void
    {
        collect($this->data['values'] ?? [])
            ->each(function ($value, $field) {
                $fieldModel = ModelsField::where('ulid', $field)->first();

                $value = $this->prepareValue($value);

                if ($this->shouldDeleteValue($value)) {
                    $this->deleteValue($field);

                    return;
                }

                if ($fieldModel && in_array($fieldModel->field_type, ['builder', 'repeater'])) {
                    $this->handleContainerField($value, $field);

                    return;
                }

                $this->updateOrCreateValue($value, $field);
            });
    }

    protected function prepareValue($value)
    {
        return isset($value['value']) && is_array($value['value']) ? json_encode($value['value']) : $value;
    }

    protected function shouldDeleteValue($value): bool
    {
        return blank($value);
    }

    protected function deleteValue($field): void
    {
        $this->record->values()->where([
            'content_ulid' => $this->record->getKey(),
            'field_ulid' => $field,
        ])->delete();
    }

    protected function handleContainerField($value, $field): void
    {
        $value = $this->decodeAllJsonStrings($value);

        $this->record->values()->updateOrCreate([
            'content_ulid' => $this->record->getKey(),
            'field_ulid' => $field,
        ], [
            'value' => is_array($value) ? json_encode($value) : $value,
        ]);
    }

    protected function updateOrCreateValue($value, $field): void
    {
        $this->record->values()->updateOrCreate([
            'content_ulid' => $this->record->getKey(),
            'field_ulid' => $field,
        ], [
            'value' => is_array($value) ? json_encode($value) : $value,
        ]);
    }

    protected function syncAuthors(): void
    {
        $this->record->authors()->syncWithoutDetaching(Auth::id());
    }

    protected function decodeAllJsonStrings($data, $path = '')
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $currentPath = $path === '' ? $key : $path . '.' . $key;
                if (is_string($value)) {
                    $decoded = $value;
                    $decodeCount = 0;
                    while (is_string($decoded)) {
                        $json = json_decode($decoded, true);
                        if ($json !== null && (is_array($json) || is_object($json))) {
                            $decoded = $json;
                            $decodeCount++;
                        } else {
                            break;
                        }
                    }
                    if ($decodeCount > 0) {
                        $data[$key] = $this->decodeAllJsonStrings($decoded, $currentPath);
                    }
                } elseif (is_array($value)) {
                    $data[$key] = $this->decodeAllJsonStrings($value, $currentPath);
                }
            }
        }

        return $data;
    }
}
