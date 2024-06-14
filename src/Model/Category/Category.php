<?php declare (strict_types = 1);

namespace HMnet\Publisher2\Model\Category;

use HMnet\Publisher2\Model\Model;

class Category extends Model {
	public string $name;
	public string $parentId;
	public string $productAssignmentType = 'product';
	public string $type = 'page';
	public string $salesChannelId;
	public string $cmsPageId;
	public bool $displayNestedProducts = true;
	public bool $active = true;

	public function id(): string {
		return md5($this->name . $this->parentId);
	}

	public function __construct(string $name, ?string $parentId) {
		$this->name = $name;
		$this->parentId = $parentId ?? $_ENV['SW_ROOT_CATEGORY_ID'];
	}

	public function serialize(): array
	{
		return [
			'id' => $this->id(),
			'name' => $this->name,
			'parentId' => $this->parentId,
			'productAssignmentType' => $this->productAssignmentType,
			'type' => $this->type,
			'salesChannelId' => $this->salesChannelId,
			'cmsPageId' => $this->cmsPageId,
			'displayNestedProducts' => $this->displayNestedProducts,
			'active' => $this->active,
		];
	}
}