<?php

namespace EscolaLms\Images\Repositories;

use EscolaLms\Core\Repositories\BaseRepository;
use EscolaLms\Images\Models\ImageCache;
use EscolaLms\Images\Repositories\Contracts\ImageCacheRepositoryContract;

class ImageCacheRepository extends BaseRepository implements ImageCacheRepositoryContract
{
    /** @var array<int, string>  */
    protected array $fieldSearchable = [
        'path',
    ];

    /**
     * @return array<int, string>
     */
    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return ImageCache::class;
    }
}
