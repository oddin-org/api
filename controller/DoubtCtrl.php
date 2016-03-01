<?php
namespace BossEdu\Controller;

use BossEdu\Model\Doubt;
use BossEdu\Model\DoubtQuery;
use BossEdu\Model\Map\DoubtTableMap;
use BossEdu\Model\PdLike;
use BossEdu\Model\PdLikeQuery;
use BossEdu\Util\Util;
use Jacwright\RestServer\RestException;
use Propel\Runtime\ActiveQuery\Criteria;

class DoubtCtrl
{
    public function authorize()
    {
        return AuthCtrl::check();
    }

    /**
     * @url POST /instruction/$instruction_id/presentation/$presentation_id/doubt
     */
    public function newDoubt($instruction_id, $presentation_id)
    {
        header("Content-Type: application/json");

        $presentation_id = urldecode($presentation_id);
        $person = $_SESSION["id"];

        if (PresentationCtrl::auth($presentation_id, $person, 0)) {
            $data = Util::getPostContents("lower");
            $data["status"] = 0;
            $data["person_id"] = $person;
            $data["presentation_id"] = $presentation_id;

            $doubt = new Doubt();
            $doubt->fromArray($data, DoubtTableMap::TYPE_FIELDNAME);
            $doubt->save();

            $doubt = DoubtQuery::create()
                ->filterById($doubt->getId())
                ->withColumn("Doubt.CreatedAt::date", "\"Doubt.Date\"")
                ->withColumn("Doubt.CreatedAt::time", "\"Doubt.Time\"")
                ->select([
                    "Doubt.Id"
                    , "Doubt.Status"
                    , "Doubt.Text"
                    , "Doubt.Anonymous"
                    , "Doubt.Understand"
                    , "Doubt.PresentationId"
                    , "Doubt.PersonId"
                ])
                ->findOne();

            echo json_encode(Util::adjustArrayCase(Util::namespacedArrayToNormal($doubt, "Doubt"), "underscore"));
        } else {
            throw new RestException(401, "Unauthorized");
        }
    }

    /**
     * @url GET /instruction/$instruction_id/presentation/$presentation_id/doubt
     */
    public function getDoubts($instruction_id, $presentation_id)
    {
        header("Content-Type: application/json");

        $presentation_id = urldecode($presentation_id);
        $person = $_SESSION["id"];

        if (PresentationCtrl::auth($presentation_id, $person, 0)) {
            $doubts = PdLikeQuery::create()
                ->join("PdLike.Doubt", Criteria::RIGHT_JOIN)
                ->join("Doubt.Person")
                ->where("Doubt.PresentationId = ?", $presentation_id)
                ->_if(isset($_GET["status"]))
                ->where("Doubt.Status = ?", $_GET["status"])
                ->_endif()
                ->select([
                    "Doubt.Id"
                    , "Doubt.Status"
                    , "Doubt.Text"
                    , "Doubt.CreatedAt"
                    , "Doubt.Anonymous"
                    , "Doubt.PresentationId"
                    , "Person.Id"
                    , "Person.Name"
                ])
                ->withColumn("count(PdLike.DoubtId)", "\"Doubt.Likes\"")
                ->withColumn("(select count(doubt_id) from contribution where doubt_id = Doubt.Id)", "\"Doubt.Contributions\"")
                ->withColumn($person." in(select person_id from pd_like where doubt_id = Doubt.Id)", "\"Doubt.Like\"")
                ->groupByDoubtId()
                ->groupByPersonId()
                ->find()
                ->toArray();

            $response = [];

            for ($i = 0, $length = count($doubts); $i < $length; $i++) {
                if ($doubts[$i]["Person.Id"] == $person) {
                    $doubts[$i]["Person.Name"] = "Eu";
                } else if ($doubts[$i]["Doubt.Anonymous"]) {
                    $doubts[$i]["Person.Id"] = 0;
                    $doubts[$i]["Person.Name"] = "Anônimo";
                }
            }

            $doubts = Util::namespacedArrayToNormal($doubts, "Doubt");
            for ($i = 0, $length = count($doubts); $i < $length; $i++) {
                $response[$doubts[$i]["Id"]] = $doubts[$i];
            }

            $response = ["doubts" =>
                Util::adjustArrayCase($response, "lower")
            ];

            echo json_encode($response);
        } else {
            throw new RestException(401, "Unauthorized");
        }
    }

    /**
     * @url GET /instruction/$instruction_id/presentation/$presentation/doubt/$doubt_id
     */
    public function getDoubt($instruction_id, $presentation_id, $doubt_id)
    {
        header("Content-Type: application/json");

        $presentation_id = urldecode($presentation_id);
        $doubt_id = urldecode($doubt_id);
        $person = $_SESSION["id"];

        if (PresentationCtrl::auth($presentation_id, $person, 0)) {
            $doubts = PdLikeQuery::create()
                ->join("PdLike.Doubt", Criteria::RIGHT_JOIN)
                ->join("Doubt.Person")
                ->where("Doubt.PresentationId = ?", $presentation_id)
                ->where("Doubt.Id = ?", $doubt_id)
                ->select([
                    "Doubt.Id"
                    , "Doubt.Status"
                    , "Doubt.Text"
                    , "Doubt.CreatedAt"
                    , "Doubt.Anonymous"
                    , "Doubt.PresentationId"
                    , "Person.Id"
                    , "Person.Name"
                ])
                ->withColumn("count(PdLike.DoubtId)", "\"Doubt.Likes\"")
                ->withColumn("(select count(doubt_id) from contribution where doubt_id = Doubt.Id)", "\"Doubt.Contributions\"")
                ->withColumn($person." in(select person_id from pd_like where doubt_id = Doubt.Id)", "\"Doubt.Like\"")
                ->groupByDoubtId()
                ->groupByPersonId()
                ->findOne();

            if ($doubts["Person.Id"] == $person) {
                $doubts["Person.Name"] = "Eu";
            } else if ($doubts["Doubt.Anonymous"]) {
                $doubts["Person.Id"] = 0;
                $doubts["Person.Name"] = "Anônimo";
            }

            $doubts = Util::namespacedArrayToNormal($doubts, "Doubt");
            $doubts = Util::adjustArrayCase($doubts, "lower");

            echo json_encode($doubts);
        } else {
            throw new RestException(401, "Unauthorized");
        }
    }

    /**
     * @url POST /instruction/$instruction_id/presentation/$presentation_id/doubt/$doubt_id/like
     */
    public function like($instruction_id, $presentation_id, $doubt_id)
    {
        header("Content-Type: application/json");

        $presentation_id = urldecode($presentation_id);
        $doubt_id = urldecode($doubt_id);
        $person = $_SESSION["id"];

        if (PresentationCtrl::auth($presentation_id, $person, 0)) {
            $d = DoubtQuery::create()
                ->filterById($doubt_id)
                ->findOne();

            if (!($d->getPersonId() == $person)) {
                $pdLike = new PdLike();
                $pdLike->setDoubtId($doubt_id);
                $pdLike->setPersonId($person);
                $pdLike->save();
            } else {
                throw new RestException(401, "Forever Alone");
            }
        } else {
            throw new RestException(401, "Unauthorized");
        }
    }

    /**
     * @url DELETE /instruction/$instruction_id/presentation/$presentation_id/doubt/$doubt_id/like
     */
    public function removeLike($instruction_id, $presentation_id, $doubt_id)
    {
        header("Content-Type: application/json");

        $presentation_id = urldecode($presentation_id);
        $doubt_id = urldecode($doubt_id);
        $person = $_SESSION["id"];

        if (PresentationCtrl::auth($presentation_id, $person, 0)) {
            $d = DoubtQuery::create()
                ->filterById($doubt_id)
                ->findOne();

            if (!($d->getPersonId() == $person)) {
                $pdLike = new PdLike();
                $pdLike->setDoubtId($doubt_id);
                $pdLike->setPersonId($person);
                $pdLike->delete();
            } else {
                throw new RestException(401, "Forever Alone");
            }
        } else {
            throw new RestException(401, "Unauthorized");
        }
    }

    /**
     * @url POST /instruction/$instruction_id/presentation/$presentation_id/doubt/$doubt_id/understand
     */
    public function understand($instruction_id, $presentation_id, $doubt_id)
    {
        $presentation_id = urldecode($presentation_id);
        $doubt_id = urldecode($doubt_id);
        $person = $_SESSION["id"];

        if (PresentationCtrl::auth($presentation_id, $person, 0)) {
            $d = PdLikeQuery::create()
                ->join("PdLike.Doubt")
                ->where("Doubt.Id = ?", $doubt_id)
                ->where("Doubt.Status = ?", 2)
                ->where("PdLike.PersonId = ?", $person)
                ->findOne();

            if ($d) {
                PdLikeQuery::create()
                    ->where("PdLike.DoubtId = ?", $doubt_id)
                    ->where("PdLike.PersonId = ?", $person)
                    ->update(["Understand" => true]);
            } else {
                throw new RestException(401, "Unauthorized");
            }
        } else {
            throw new RestException(401, "Unauthorized");
        }
    }

    /**
     * @url DELETE /instruction/$instruction_id/presentation/$presentation_id/doubt/$doubt_id/understand
     */
    public function removeUnderstand($instruction_id, $presentation_id, $doubt_id)
    {
        $presentation_id = urldecode($presentation_id);
        $doubt_id = urldecode($doubt_id);
        $person = $_SESSION["id"];

        if (PresentationCtrl::auth($presentation_id, $person, 0)) {
            $d = PdLikeQuery::create()
                ->join("PdLike.Doubt")
                ->where("PdLike.Idd = ?", $doubt_id)
                ->where("PdLike.Idp = ?", $person)
                ->where("Doubt.Status = ?", 2)
                ->where("PdLike.Understand = ?", true)
                ->findOne();

            if ($d) {
                PdLikeQuery::create()
                    ->where("PdLike.Idd = ?", $doubt_id)
                    ->where("PdLike.Idp = ?", $person)
                    ->update(["Understand" => false]);
            } else {
                throw new RestException(401, "Unauthorized");
            }
        } else {
            throw new RestException(401, "Unauthorized");
        }
    }

    /**
     * @url POST /presentation/$presentation_id/doubt/$doubt_id/change-status
     */
    public function changeStatus($presentation_id, $doubt_id)
    {
        header("Content-Type: application/json");

        $presentation_id = urldecode($presentation_id);
        $doubt_id = urldecode($doubt_id);
        $person = $_SESSION["id"];

        if (PresentationCtrl::auth($presentation_id, $person, 2)) {
            $data = Util::getPostContents("lower");

            DoubtQuery::create()
                ->filterById($doubt_id)
                ->update(["Status" => $data["status"]]);
        } else {
            throw new RestException(401, "Unauthorized");
        }
    }
}
