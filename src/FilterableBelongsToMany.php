<?php

namespace SPRIGS\FilterableBelongsToMany;

use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;
use Laravel\Nova\Util;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\ResourceRelationshipGuesser;

class FilterableBelongsToMany extends Field
{
    const LIST_STYLE = 'list';

    const GROUP_STYLE = 'group';

    public $availableFilters = [];
    public $hiddenFields = [];
    public $searchableFields = [];    

    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'filterable-belongs-to-many';

    /**
     * The text alignment for the field's text in tables.
     *
     * @var string
     */
    public $textAlign = 'center';

    /**
     * The class name of the related resource.
     *
     * @var class-string<\Laravel\Nova\Resource>
     */
    public $resourceClass;

    /**
     * The URI key of the related resource.
     *
     * @var string
     */
    public $resourceName;

    /**
     * The name of the Eloquent "belongs to many" relationship.
     *
     * @var string
     */
    public $manyToManyRelationship;

    /**
     * The visual style to use when display the tags.
     *
     * @var string
     */
    public $style = 'group';

    /**
     * Indicates if Nova should show a preview modal for the tag.
     *
     * @var bool
     */
    public $withPreview = false;

    /**
     * Create a new field.
     *
     * @param  string  $name
     * @param  string|null  $attribute
     * @param  class-string<\Laravel\Nova\Resource>|null  $resource
     * @return void
     */
    public function __construct($name, $attribute = null, $resource = null)
    {
        parent::__construct($name, $attribute);

        $resource = $resource ?? ResourceRelationshipGuesser::guessResource($name);

        $this->resourceClass = $resource;
        $this->resourceName = $resource::uriKey();
        $this->manyToManyRelationship = $this->attribute = $attribute ?? ResourceRelationshipGuesser::guessRelation($name);
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  string  $requestAttribute
     * @param  object  $model
     * @param  string  $attribute
     * @return \Closure
     */
    protected function fillAttributeFromRequest(NovaRequest $request, $requestAttribute, $model, $attribute)
    {
        return function () use ($model, $attribute, $request) {
            $model->{$attribute}()->sync(
                $this->prepareRelations($request, $attribute)
            );
        };
    }

    /**
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  string  $attribute
     * @return array<int, string>
     */
    protected function prepareRelations(NovaRequest $request, string $attribute)
    {
        if (! $request->filled($attribute)) {
            return [];
        }

        return collect(json_decode($request->{$attribute}, true))
            ->pluck('value')
            ->filter()
            ->all();
    }

    /**
     * Resolve the given attribute from the given resource.
     *
     * @param  mixed  $resource
     * @param  string  $attribute
     * @return array
     */
    protected function resolveAttribute($resource, $attribute)
    {
        return $resource->{$attribute}
            ->map(function ($model) {
                return $this->transformResult(
                    app(NovaRequest::class), Nova::newResourceFromModel($model)
                );
            })->values()->all();
    }

    /**
     * Set the field to display as a list of rows.
     *
     * @return $this
     */
    public function displayAsList()
    {
        $this->style = static::LIST_STYLE;

        return $this;
    }

    /**
     * Set the field to display a preview modal when clicking the tag.
     *
     * @return $this
     */
    public function withPreview()
    {
        $this->withPreview = true;

        return $this;
    }
    
    /**
     * Add filters to the field.
     *
     * @return $this
     */
    public function addFilters($filtersArray = [])
    {
        $this->availableFilters = $filtersArray;
        return $this;
    }
    
    /**
     * Hide fields.
     *
     * @return $this
     */
    public function hideFields($hiddenFieldsArray = [])
    {
        $this->hiddenFields = $hiddenFieldsArray;
        return $this;
    }
    
    /**
     * Searchable fields fields.
     *
     * @return $this
     */
    public function makeSearchable($searchableFieldsArray = [])
    {
        $this->searchableFields = $searchableFieldsArray;
        return $this;
    }

    /**
     * Transform the result from resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Laravel\Nova\Resource  $resource
     * @return array
     */
    protected function transformResult(NovaRequest $request, $resource)
    {
        return array_filter([
            'avatar' => $resource->resolveAvatarUrl($request),
            'display' => (string) $resource->title(),
            'subtitle' => $resource->subtitle(),
            'value' => Util::safeInt($resource->getKey()),
        ]);
    }

    /**
     * Prepare the field for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        /** @phpstan-ignore-next-line */
        return with(app(NovaRequest::class), function ($request) {
            return array_merge([
                'style' => $this->style,
                'availableFilters' => $this->availableFilters,
                'hiddenFields' => $this->hiddenFields,
                'searchableFields' => $this->searchableFields,
                'belongsToManyRelationship' => $this->manyToManyRelationship,
                'resourceName' => $this->resourceName,
                'singularLabel' => $this->singularLabel ?? $this->resourceClass::singularLabel(),
                'validationKey' => $this->validationKey()
            ], parent::jsonSerialize());
        });
    }
}
