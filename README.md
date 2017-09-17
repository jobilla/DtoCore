# DtoCore for Laravel
 
*This project is currently internally in production use, but open-source wise still in very beta-ish state*

## Prerequisites
This library is designed to work with Laravel > v5.2 applications, and utilises Laravel Validator + Collections & Routing.

## Instructions

When creating Dto classes, extend the `DtoAbstract.php` from this library (example below) 

```
<?php 

namespace App\Dto\v2\Company;

use App\Dto\v2\Location\Location;
use Jobilla\DtoCore\DtoAbstract;

class Company extends DtoAbstract
{
    /**
     * @var array
     */
    protected $items = [
        // Specify the fields here
    ];

    /**
     * Specify optional sub Dtos here 
     * @var array
     */
    protected $subtypes = [
        'location' => Location::class,
    ];

    /**
     * @var array
     */
    protected $rules = [
        // Laravel Validator's syntax validation rules for every field go here
    ];

    /**
     * @param \App\Models\Company $company
     *
     * @return $this
     */
    public function populateFromModel(\App\Models\Company $company): Company
    {
        // Specify strategy for instantiation here..
        
        // etc...
        $this['meta_keywords']    = $company->meta_keywords;
        $this['meta_description'] = $company->meta_description;
        $this['location']         = Location::from($company->city)->toArray();
        // etc..

        // Check for validation flag & self-validate on instantiation
        $this->validation && $this->validate();

        return $this;
    }
}

```

Use for validating both incoming and outgoing Requests in Controllers (or implement custom route-model binding -type resolver), example below.

```
    /**
     * Show company
     *
     * @input int
     * @output \App\Dto\v2\Company\Company
     *
     * @param int $id
     *
     * @return JsonResponse
     */
    public function show(int $id)
    {
        $dto = CompanyDto::from(Company::findOrFail($id));

        return $this->response->respondDto($dto);
    }

    /**
     * Store a new company
     *
     * @input \App\Dto\v2\Company\Company
     * @output \App\Dto\v2\Company\Company
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $dto     = CompanyDto::from($request->all());
        $company = CompanyRepository::store($dto);

        return $this->show($company->id);
    }
```
**NOTE: @input & @output declarations in the docblocks allow automatic OpenAPI documentation generation..**

For automatic OpenAPI format (formerly known as Swagger) documentation, call `ComposeDocumentation` Command from the Library, and return the structure of `ComposeDocumentation::get()` from the proper route for inspection.

Here's a brilliant OpenAPI doc reader, that you can use as an independent GUI for easy professional API-docs. (https://github.com/Rebilly/ReDoc)

### Todo:
- [ ] Setup proper CI pipeline with jobilla/coding-rules checks
- [ ] Basic PHPUnit tests
- [x] Some sort of primitive documentation

#### Disclaimer
* Original author: Sander Kiloman (@sass777)
* License information will be added later..