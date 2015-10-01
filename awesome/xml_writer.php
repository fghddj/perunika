<?php

mysql_connect('localhost', 'dbuser', '123');
mysql_select_db('perunika_presta');

$queryProduct = "SELECT m.name as manufacturerName,
                p.id_product, p.price, p.id_manufacturer,
                pl.name as productName, pl.description_short, pl.id_lang, pl.link_rewrite AS productLink
        FROM ps_product p
        LEFT JOIN ps_product_lang pl ON p.id_product = pl.id_product
        LEFT JOIN ps_manufacturer m ON p.id_manufacturer = m.id_manufacturer
        where pl.id_lang =2";

$resultProduct = mysql_query($queryProduct);

$productArray = array();

while ($row = mysql_fetch_assoc($resultProduct)) {
    $productArray[] = $row;
};

$q = "SELECT * FROM ps_attribute_lang where id_lang =2";

$queryStock = "SELECT pa.id_product,
                      al.id_attribute, al.name as attributeName,
                      sa.quantity
        FROM ps_product_attribute pa
        LEFT JOIN ps_product_attribute_combination pac ON pa.id_product_attribute = pac.id_product_attribute
        LEFT JOIN ps_attribute_lang al ON pac.id_attribute = al.id_attribute
        LEFT JOIN ps_stock_available sa ON pa.id_product_attribute = sa.id_product_attribute
        where al.id_lang =2";

$resultStock = mysql_query($queryStock);

$stockArray = array();

while ($row = mysql_fetch_assoc($resultStock)) {
    $stockArray[] = $row;
};

$queryFeature = "SELECT fp.id_product, fp.id_feature,
                        fvl.value
        FROM ps_feature_product fp
        LEFT JOIN ps_feature_value_lang fvl ON fp.id_feature_value = fvl.id_feature_value
        where fvl.id_lang =2";

$resultFeature = mysql_query($queryFeature);

$featureArray = array();

while ($row = mysql_fetch_assoc($resultFeature)) {
    $featureArray[] = $row;
};

$queryCategory = "SELECT cp.id_product,
                          cl.id_category, cl.name AS categoryName, cl.link_rewrite AS categoryLink
        FROM ps_category_product cp
        LEFT JOIN ps_category_lang cl ON cp.id_category = cl.id_category
        where cl.id_lang =2";

$resultCategory = mysql_query($queryCategory);

$categoryArray = array();

while ($row = mysql_fetch_assoc($resultCategory)) {
    $categoryArray[] = $row;
};

$queryPrice = "SELECT p.price, p.id_product,
                      sp.price as specialPrice, sp.reduction, sp.reduction_type, sp.from, sp.to
        FROM ps_product p
        LEFT JOIN ps_specific_price sp ON p.id_product = sp.id_product";

$resultPrice = mysql_query($queryPrice);

$priceArray = array();

while ($row = mysql_fetch_assoc($resultPrice)) {
    $priceArray[] = $row;
};

$queryImage = "SELECT i.id_product, i.id_image
        FROM ps_image i";

$resultImage = mysql_query($queryImage);

$imageArray = array();

while ($row = mysql_fetch_assoc($resultImage)) {
    $imageArray[] = $row;
};

header('Content-type: text/xml');
$xml = new XMLWriter('1.0', 'utf-8');

$xml->openURI("php://output");
$xml->startDocument();
$xml->setIndent(true);

$xml->startElement('Podjetje');
    $xml->writeAttribute('id', 'perunika.org');

    $xml->startElement('Izdelki');

    foreach($productArray as $product) {

        $images = getImagesForProduct($imageArray, $product['id_product']);
        $categories = getCategoryForProduct($categoryArray, $product['id_product']);
        $prices = getPriceForProduct($priceArray, $product['id_product']);
        $stocks = getStockForProduct($stockArray, $product['id_product']);

        $xml->startElement("Izdelek");

        $xml->startElement("Opis");
            $xml->writeRaw(strip($product['description_short']));
        $xml->endElement();

        $xml->startElement("ImeIzdelka");
            $xml->writeRaw(strip($product['productName']));
        $xml->endElement();

        foreach($images as $image)
        {
            $xml->startElement("SlikaVelika");
            $xml->writeRaw("http://perunika.org/" . $image['id_image'] . "/" . $image['id_image'] . ".jpg");
            $xml->endElement();
        }


        $xml->startElement("Znamka");
            $xml->writeRaw($product['manufacturerName']);
        $xml->endElement();

        $xml->startElement("Kategorije");
            foreach($categories as $category)
            {
                $xml->startElement("Kategorija");
                    $xml->writeAttribute('id', $category['id_category']);
                    $xml->writeAttribute('name', strip($category['categoryName']));
                $xml->endElement();
            }
        $xml->endElement();

        $xml->startElement("SkuProdajalca");
            $xml->writeRaw($product['id_product']);
        $xml->endElement();

        $xml->startElement("OpcijeIzdelka");
            $xml->startElement("SkupinaOpcijeIzdelka");
        $xml->writeAttribute('name', 'Velikost');

        foreach($stocks as $stock)
        {
            $xml->startElement("OpcijaIzdelka");
                $xml->writeAttribute('name', strip($stock['attributeName']));
                $xml->writeAttribute('quantity', $stock['quantity']);
            $xml->endElement();
        }

        $xml->endElement();
        $xml->endElement();

        if (count($categories) > 0)
        {
            $xml->startElement("PovezavaIzdelka");
                $xml->writeRaw("http://perunika.org/si/" . $categories[0]['categoryLink'] . "/" . $product['id_product'] . "-" . $product['productLink'] . ".html");
            $xml->endElement();
        }

        $xml->startElement("Cena");
            $xml->writeRaw($product['price']);
        $xml->endElement();

        foreach($prices as $price)
        {
            $xml->startElement("SalePrice");
                $xml->writeRaw($price['price'] * (1 - $price['reduction']));
            $xml->endElement();
        }

        foreach($prices as $price)
        {
            $xml->startElement("SaleEndDate");
                $xml->writeRaw($price['to']);
            $xml->endElement();
        }

        foreach($prices as $price)
        {
            $xml->startElement("SaleStartDate");
                $xml->writeRaw($price['from']);
            $xml->endElement();
        }

        $xml->endElement();
    }

    $xml->endElement();
$xml->endElement();


$xml->flush();




function getPriceForProduct($prices, $id_product)
{
    $result = array();

    foreach ($prices as $price)
    {
        if($price['id_product'] == $id_product)
        {
            $result[] = $price;
        }
    }

    return $result;
}

function getCategoryForProduct($categories, $id_product)
{
    $result = array();

    foreach ($categories as $category)
    {
        if($category['id_product'] == $id_product)
        {
            $result[] = $category;
        }
    }

    return $result;

}

function getFeatureForProduct($features, $id_product)
{
    $result = array();

    foreach ($features as $feature)
    {
        if($feature['id_product'] == $id_product)
        {
            $result[] = $feature;
        }
    }

    return $result;
}

function getStockForProduct($stocks, $id_product)
{
    $result = array();

    foreach ($stocks as $stock)
    {
        if($stock['id_product'] == $id_product)
        {
            $result[] = $stock;
        }
    }

    return $result;
}

function getImagesForProduct($images, $id_product)
{
    $result = array();

    foreach($images as $image)
    {
        if($image['id_product'] == $id_product)
        {
            $result[] = $image;
        }

    }

    return $result;
}

function strip($name)
{
    return trim(addslashes(strip_tags($name)));
}