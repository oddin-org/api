# == Schema Information
#
# Table name: presentations
#
#  id             :integer          not null, primary key
#  subject        :string(100)      not null
#  status         :integer          default(0), not null
#  created_at     :datetime         not null
#  instruction_id :integer          not null
#  person_id      :integer          not null
#

class PresentationSerializer < ActiveModel::Serializer
  attributes :id, :subject, :status, :created_at

  has_one :instruction
  has_one :person
end
