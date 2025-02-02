<?php

namespace App\Controller\Admin;

use App\Entity\BankBrand;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
  * @IsGranted("ROLE_ADMIN", message="You are not allowed to access the admin dashboard.")
  */
class BankBrandCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BankBrand::class;
    }
}
