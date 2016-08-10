<?php

class Waindigo_CustomFields_Listener_FileHealthCheck
{

    public static function fileHealthCheck(XenForo_ControllerAdmin_Abstract $controller, array &$hashes)
    {
        $hashes = array_merge($hashes,
            array(
                'library/Waindigo/CustomFields/AttachmentHandler/CustomField.php' => '760abd48ff368db2d6ed593faa0f7161',
                'library/Waindigo/CustomFields/ControllerAdmin/Abstract.php' => 'd41d6c1d59f56cb56acd254aaa4c9130',
                'library/Waindigo/CustomFields/ControllerAdmin/ResourceField.php' => '355e5f39d3e05c831557940c5b718001',
                'library/Waindigo/CustomFields/ControllerAdmin/SocialForumField.php' => 'f33ccd1306934843a164361f47291cec',
                'library/Waindigo/CustomFields/ControllerAdmin/ThreadField.php' => 'fd8b78eb9399c9edc7ed6f97b0418bfc',
                'library/Waindigo/CustomFields/ControllerPublic/CustomFieldContent.php' => '33d67e0a513772ee4a043c4a67cfd539',
                'library/Waindigo/CustomFields/DataWriter/AbstractField.php' => 'abf023c5ec285bc9c3fa9b1765a95ffe',
                'library/Waindigo/CustomFields/DataWriter/Attachment.php' => '5b3957948b7bf8c3bdf2508322eae2b5',
                'library/Waindigo/CustomFields/DataWriter/ResourceField.php' => '2cac90f80f91ad1732aad161ee9ae396',
                'library/Waindigo/CustomFields/DataWriter/ResourceFieldGroup.php' => '9c9e6e971140de7d84120ac4397ef27f',
                'library/Waindigo/CustomFields/DataWriter/SocialForumField.php' => '94bdee8b0678c000aed8dc1ac40c004e',
                'library/Waindigo/CustomFields/DataWriter/SocialForumFieldGroup.php' => '6092aca9e19de4438ea57104e90943a4',
                'library/Waindigo/CustomFields/DataWriter/ThreadField.php' => 'f55674c50d3dc0ded16ad8757f4875f2',
                'library/Waindigo/CustomFields/DataWriter/ThreadFieldGroup.php' => 'b3ff8fe7a651df4632a776988d9459f3',
                'library/Waindigo/CustomFields/Definition/Abstract.php' => '48712785257ba1de30ef50eb1c8eaa3d',
                'library/Waindigo/CustomFields/Definition/ResourceField.php' => '74b9de0144380ecbe91c534a72e8515c',
                'library/Waindigo/CustomFields/Definition/SocialForumField.php' => '15d8370a64b16be3baf92bd03b5ae646',
                'library/Waindigo/CustomFields/Definition/ThreadField.php' => '2f122e6816cffce74870fcafbe2a24c2',
                'library/Waindigo/CustomFields/Extend/Waindigo/Library/ControllerAdmin/Library.php' => '5794ef957eeb6ac02072a3820e0eddfb',
                'library/Waindigo/CustomFields/Extend/Waindigo/Library/ControllerPublic/Article.php' => '00c59e45f225ef919940eceeaba51dd4',
                'library/Waindigo/CustomFields/Extend/Waindigo/Library/ControllerPublic/Library.php' => '28cf90904007813f40abfefb156baae7',
                'library/Waindigo/CustomFields/Extend/Waindigo/Library/DataWriter/Article.php' => 'e7d9d73da734bdc96588c54d05fc32a1',
                'library/Waindigo/CustomFields/Extend/Waindigo/Library/DataWriter/Library.php' => 'a1d2c1d53c03fa1c4129a9d074f28654',
                'library/Waindigo/CustomFields/Extend/Waindigo/Library/Install/Controller.php' => '043179ebde24c0f274dacd51c1cefab3',
                'library/Waindigo/CustomFields/Extend/Waindigo/Library/Install.php' => 'da80d97dd3fd7672e126981e3d555e14',
                'library/Waindigo/CustomFields/Extend/Waindigo/Library/Search/DataHandler/ArticlePage.php' => 'fe61818337b28aae7ad3d17af23f2f0f',
                'library/Waindigo/CustomFields/Extend/Waindigo/Library/ViewPublic/Article/View.php' => '9793c79007cae806733bd53f69a9a6cf',
                'library/Waindigo/CustomFields/Extend/Waindigo/NoForo/Model/NoForo.php' => 'a32ec163d30aff25882a23d5ec3e60b1',
                'library/Waindigo/CustomFields/Extend/Waindigo/SocialGroups/ControllerAdmin/SocialCategory.php' => '8c7373fe0c459996f9e7d951636964e0',
                'library/Waindigo/CustomFields/Extend/Waindigo/SocialGroups/ControllerPublic/SocialCategory.php' => '573c20a198400cd5d0a272564ef0afcf',
                'library/Waindigo/CustomFields/Extend/Waindigo/SocialGroups/ControllerPublic/SocialForum.php' => 'ef40b6df46cf59d96d655635378b8e60',
                'library/Waindigo/CustomFields/Extend/Waindigo/SocialGroups/DataWriter/SocialForum.php' => 'b460142e9b1b9563c628ab213cecc061',
                'library/Waindigo/CustomFields/Extend/Waindigo/SocialGroups/Install/Controller.php' => '9025d16fc9699f197fc1f588505e34b7',
                'library/Waindigo/CustomFields/Extend/Waindigo/SocialGroups/ViewPublic/SocialForum/View.php' => '668dca39c16cde458b289366c0c18795',
                'library/Waindigo/CustomFields/Extend/Waindigo/UserSearch/Search/DataHandler/User.php' => 'd8e07c5b98124258d83fe09775dffd17',
                'library/Waindigo/CustomFields/Extend/XenForo/ControllerAdmin/Forum.php' => 'a08ebd059923d092ca423b078b70332e',
                'library/Waindigo/CustomFields/Extend/XenForo/ControllerAdmin/UserField.php' => '95842e84bc1aafdfe46cd9eb1329d6be',
                'library/Waindigo/CustomFields/Extend/XenForo/ControllerPublic/Forum.php' => 'd0b3a7b322aa641fb990d81de4c7c6f2',
                'library/Waindigo/CustomFields/Extend/XenForo/ControllerPublic/Search.php' => '0d11572a7cafc27bff64623046eda9cf',
                'library/Waindigo/CustomFields/Extend/XenForo/ControllerPublic/Thread.php' => 'e6c52aa819c5637e82afd68ca96e07a5',
                'library/Waindigo/CustomFields/Extend/XenForo/DataWriter/Discussion/Thread.php' => 'db939418aeeab42c3ee79c5fbc3f13a0',
                'library/Waindigo/CustomFields/Extend/XenForo/DataWriter/Forum.php' => 'bbbb05ccdcba5200b44a8df80f5f8479',
                'library/Waindigo/CustomFields/Extend/XenForo/DataWriter/User.php' => 'bb9e6fe1c806b49b18ad3689f2de3356',
                'library/Waindigo/CustomFields/Extend/XenForo/DataWriter/UserField.php' => '60669eeccb87eddf9fbeae7552432758',
                'library/Waindigo/CustomFields/Extend/XenForo/Model/AddOn.php' => '91d0b4ee96661930269ef21b4608a33f',
                'library/Waindigo/CustomFields/Extend/XenForo/Model/Phrase.php' => '842e79819ebfd59fee8aba8d144e5560',
                'library/Waindigo/CustomFields/Extend/XenForo/Model/Search.php' => 'b77cb3c80576d7e54d75f0a89e73e1ca',
                'library/Waindigo/CustomFields/Extend/XenForo/Model/Thread.php' => '39ffb132d372c52c265707b535cbf080',
                'library/Waindigo/CustomFields/Extend/XenForo/Model/ThreadRedirect.php' => '596eed1e542ec8a63026a9e122d27165',
                'library/Waindigo/CustomFields/Extend/XenForo/Model/UserField.php' => '2e5ee9b280347090b2fad7b341ab569c',
                'library/Waindigo/CustomFields/Extend/XenForo/Search/DataHandler/Post.php' => '80269630106f0f91e481ddd8eaae5071',
                'library/Waindigo/CustomFields/Extend/XenForo/ViewPublic/Forum/View.php' => '7edc28624466b9afe99121c1935328b4',
                'library/Waindigo/CustomFields/Extend/XenForo/ViewPublic/Thread/View.php' => 'd24b0415204133c905a8ea4507c8a5d2',
                'library/Waindigo/CustomFields/Extend/XenForo/ViewPublic/Thread/ViewPosts.php' => 'df755ed6fdf2a5ce01efb34982aced59',
                'library/Waindigo/CustomFields/Extend/XenResource/ControllerAdmin/Category.php' => '2252f9e9bdd3330626c7bd2c4549f69c',
                'library/Waindigo/CustomFields/Extend/XenResource/ControllerAdmin/Field.php' => '48258b6278c7824df55cda93d1e52194',
                'library/Waindigo/CustomFields/Extend/XenResource/ControllerPublic/Resource.php' => '26eef57fe1b749cc19691c9d1cdc25e9',
                'library/Waindigo/CustomFields/Extend/XenResource/DataWriter/Category.php' => '4aaef1d2863b5db047deef5538f41f9f',
                'library/Waindigo/CustomFields/Extend/XenResource/DataWriter/Resource.php' => '65d1678feabb1420b79321a8e72a3f59',
                'library/Waindigo/CustomFields/Extend/XenResource/DataWriter/ResourceField.php' => '900a23f74c9d8d31fc285d6467a1562d',
                'library/Waindigo/CustomFields/Extend/XenResource/Model/Resource.php' => '56fef3f12129bd33a39e8ce51ac727c4',
                'library/Waindigo/CustomFields/Extend/XenResource/Model/ResourceField.php' => '7f661f49ab4443cdb50e73327bf9ffce',
                'library/Waindigo/CustomFields/Extend/XenResource/Search/DataHandler/Update.php' => '887c50c2884143ade7464daa80898e4c',
                'library/Waindigo/CustomFields/Extend/XenResource/ViewPublic/Resource/View.php' => '1fc9d0d71bab8a3b1af2807fcbee242d',
                'library/Waindigo/CustomFields/Install/Controller.php' => '0e920b306ff9b10ecc6076667bfe607f',
                'library/Waindigo/CustomFields/Listener/ContainerAdminParams.php' => '9b6608c8350b9dac5db2a64109974a70',
                'library/Waindigo/CustomFields/Listener/FrontControllerPostView.php' => 'c4d6e0f6556469dcca93a5817045f8e9',
                'library/Waindigo/CustomFields/Listener/LoadClass.php' => '2107eb2e7f925b6747bae35d5111d03b',
                'library/Waindigo/CustomFields/Listener/TemplateCreate.php' => '8d45e2f64e04b8a205331f81265b5d84',
                'library/Waindigo/CustomFields/Listener/TemplateHook.php' => 'c9e26dd5b365b61eee3230f4d3153039',
                'library/Waindigo/CustomFields/Listener/TemplatePostRender.php' => '586ff2ad378501131dc40b008e9dee92',
                'library/Waindigo/CustomFields/Model/AdminTemplate.php' => '31e867995c42d64159a63c38648b4b2e',
                'library/Waindigo/CustomFields/Model/Attachment.php' => '1a29937de1ffb9ab5c8e447223169ab2',
                'library/Waindigo/CustomFields/Model/ResourceField.php' => 'cbe918cd566a8dfdeeb7e8bf50312200',
                'library/Waindigo/CustomFields/Model/SocialForumField.php' => 'aad3874dd329a6002e4a858ca269f862',
                'library/Waindigo/CustomFields/Model/Template.php' => '44a31d0dbd781a2bc7c24fe46159a503',
                'library/Waindigo/CustomFields/Model/ThreadField.php' => '5c2d45f39f9f37a6f58f6fb47dd4b512',
                'library/Waindigo/CustomFields/Route/Prefix/CustomFieldContent.php' => '4bca5187b933266f8aa3da67a3a3b750',
                'library/Waindigo/CustomFields/Route/PrefixAdmin/ResourceFields.php' => '835a7c56871cf9b12e1d2b33e2c335f0',
                'library/Waindigo/CustomFields/Route/PrefixAdmin/SocialForumFields.php' => '01ac853e4f70bcd5f37c84b5de3a930f',
                'library/Waindigo/CustomFields/Route/PrefixAdmin/ThreadFields.php' => '059d2b8cf7be85677cd57aad20eed84a',
                'library/Waindigo/CustomFields/Search/Helper/CustomField.php' => '1578b61154bf7b135d6a1627f91cb517',
                'library/Waindigo/CustomFields/ViewAdmin/ResourceField/Export.php' => 'f20ad4c0eb67507d1f61d545d86415e1',
                'library/Waindigo/CustomFields/ViewAdmin/SocialForumField/Export.php' => '3c32dcf51181f6c1c83f81b85b70ba75',
                'library/Waindigo/CustomFields/ViewAdmin/ThreadField/Export.php' => '2441cfd08d69b3d6d0e088e9faf4042e',
                'library/Waindigo/CustomFields/ViewAdmin/UserField/Export.php' => '7dc8ad18388f0799c0a436ad468347d4',
                'library/Waindigo/CustomFields/ViewPublic/Helper/Resource.php' => 'ce01372c02be8b6bb5097e85149ea810',
                'library/Waindigo/CustomFields/ViewPublic/Helper/SocialForum.php' => '6f9f9d31e5346bd9938e59f43c57e3ab',
                'library/Waindigo/CustomFields/ViewPublic/Helper/Thread.php' => 'cf6be9d1fcc2cdc42f0c1f075862ac6d',
                'library/Waindigo/Install.php' => '00d8b93ea3458f18752c348a09a16c50',
                'library/Waindigo/Install/20150313.php' => '0acd9a035aaa29f2ed22cb3754a696f0',
                'library/Waindigo/Deferred.php' => '4649953c0a44928b5e2d4a86e7d3f48a',
                'library/Waindigo/Deferred/20150106.php' => 'c886ad117aa0d601292bc1fa0b156544',
                'library/Waindigo/Listener/ControllerPreDispatch.php' => 'f51aeb4ef6c4acbce629188b04cd3643',
                'library/Waindigo/Listener/ControllerPreDispatch/20150212.php' => 'a2f05f1e44689d39d1ce95ad461eb4c5',
                'library/Waindigo/Listener/InitDependencies.php' => '5b755bcc0e553351c40871f4181ce5b0',
                'library/Waindigo/Listener/InitDependencies/20150212.php' => '2c5d6ecedd94347715d4866d9b03112c',
                'library/Waindigo/Listener/ContainerParams.php' => 'caecff656ee0c4405c9d6aec62355031',
                'library/Waindigo/Listener/ContainerParams/20150106.php' => '7a47e4721af622260c9eaf1d57eda249',
                'library/Waindigo/Listener/Template.php' => 'b52cba9c298d9702b4536146d3ac4312',
                'library/Waindigo/Listener/Template/20150106.php' => '2bbe04f8b858a9dd2834a1ea6558d7b7',
                'library/Waindigo/Listener/FrontControllerPostView.php' => '5d509b56de8b508632fc83ae78b9de94',
                'library/Waindigo/Listener/FrontControllerPostView/20150106.php' => 'b3dffff0efb3272049b0312bf584c132',
                'library/Waindigo/Listener/LoadClass.php' => 'bfdfe90f8d484d81b05889037a4fb091',
                'library/Waindigo/Listener/LoadClass/20150106.php' => 'a962cf203ee7efe8247366e5de3862a0',
                'library/Waindigo/Listener/TemplateCreate.php' => 'db5c0d5eb8c65b1840dd437e5cca69d6',
                'library/Waindigo/Listener/TemplateCreate/20150106.php' => '197a5be81b03099661da027fa4370312',
                'library/Waindigo/Listener/TemplateHook.php' => '37c6a882bfb9d790801c94051fe3eb0d',
                'library/Waindigo/Listener/TemplateHook/20150106.php' => '49397da485e59bb06089c84ba60db5a7',
                'library/Waindigo/Listener/TemplatePostRender.php' => '820870f6c332a112de0df78a84121285',
                'library/Waindigo/Listener/TemplatePostRender/20150106.php' => '41fc17661980130f039d011cc419fc9f',
            ));
    }
}