<?php
/**
 * @package AHS\AdvertsPluginBundle
 * @author Paweł Mikołajczuk <mikolajczuk.private@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace AHS\AdvertsPluginBundle\Entity;

use Doctrine\ORM\Mapping AS ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Announcement entity
 *
 * @ORM\Entity()
 * @ORM\Table(name="plugin_adverts_announcement")
 */
class Announcement 
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer", name="id")
     * @var string
     */
    private $id;

    /**
     * @ORM\Column(type="string", name="name")
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(type="text", name="description")
     * @Assert\NotBlank(message="Musisz podac opis")
     * @var string
     */
    private $description;

    /**
     * @ORM\OneToMany(targetEntity="Image", mappedBy="announcement")
     */ 
    private $images;

    /**
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="announcement")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id")
     */  
    private $category;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="announcement")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */    
    private $user;

    /**
     * @ORM\Column(type="float", name="price")
     * @Assert\NotBlank(message="Musisz podac cenę")
     * @Assert\Range(min = "0", minMessage = "Cena musi być większa od 0")
     * @Assert\Type(type="float", message = "Cena musi być liczbą")
     * @var string
     */
    private $price;

    /**
     * @ORM\Column(type="integer", name="reads_number", nullable=true)
     * @var integer
     */
    private $reads;

    /**
     * @ORM\Column(type="datetime", name="created_at")
     * @var string
     */
    private $created_at;

    /**
     * @ORM\Column(type="boolean", name="is_active", nullable=true)
     * @var string
     */
    private $is_active;

    public function __construct() {
        $this->setCreatedAt(new \DateTime());
        $this->images = new \Doctrine\Common\Collections\ArrayCollection();
        $this->is_active = true;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
        
        return $this;
    }

    public function getCreatedAt()
    {
        return $this->created_at;
    }

    public function getValidDate()
    {
        $date = clone $this->created_at;
        $date->modify('+14 days');

        return $date;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
        
        return $this;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function setPrice($price)
    {
        $this->price = $price;
        
        return $this;
    }

    public function getCategory()
    {
        return $this->category;
    }

    public function setCategory($category)
    {
        $this->category = $category;
        
        return $this;
    }

    public function getImages()
    {
        return $this->images;
    }

    public function getFirstImage($showEmpty = null)
    {
        if (!count($this->images) && $showEmpty) {
            return array(
                'id' => null,
                'announcementPhotoId' => null,
                'imageUrl' => '/public/bundles/ahsadvertsplugin/images/small_empty.jpg',
                'thumbnailUrl' => '/public/bundles/ahsadvertsplugin/images/empty.jpg'
            );
        } else if (!count($this->images)) {
            return null;
        }

        return $this->processImage($this->images[0]);
    }

    private function processImage($image)
    {
        $newscoopImage = new \Image($image->getNewscoopImageId());
        $processedPhoto = array(
            'id' => $newscoopImage->getImageId(),
            'announcementPhotoId' => $image->getId(),
            'imageUrl' => $newscoopImage->getImageUrl(),
            'thumbnailUrl' => $newscoopImage->getThumbnailUrl()
        );

        return $processedPhoto;
    }


    public function getUser()
    {
        return $this->user;
    }

    public function setUser($user)
    {
        $this->user = $user;
        
        return $this;
    }

    public function setCreatedAt(\DateTime $created_at)
    {
        $this->created_at = $created_at;
        
        return $this;
    }

    public function getSlug()
    {
        return $this->slugify($this->name);
    }

    public function addRead()
    {
        return $this->reads = $this->reads+1;
    }

    public function getReads()
    {
        return $this->reads;
    }

    public function getIsActive()
    {
        return $this->is_active;
    }

    /**
     * Modifies a string to remove all non ASCII characters and spaces.
     */
    public function slugify($text)
    {
        $char_map = array(
            // Latin symbols
            '©' => '(c)',
            // Polish
            'Ą' => 'A', 'Ć' => 'C', 'Ę' => 'e', 'Ł' => 'L', 'Ń' => 'N', 'Ó' => 'o', 'Ś' => 'S', 'Ź' => 'Z', 
            'Ż' => 'Z', 
            'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n', 'ó' => 'o', 'ś' => 's', 'ź' => 'z',
            'ż' => 'z',
        );
        // Make custom replacements
        $text = str_replace(array_keys($char_map), $char_map, $text);
        // replace non letter or digits by -
        $text = preg_replace('~[^\\pL\d]+~u', '-', $text);
        // trim
        $text = trim($text, '-');
        // transliterate
        if (function_exists('iconv')) {
            $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        }
        // lowercase
        $text = strtolower($text);
        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);
        if (empty($text)) {
            return 'n-a';
        }
     
        return $text;
    }
}
